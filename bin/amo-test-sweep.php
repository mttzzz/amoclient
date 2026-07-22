#!/usr/bin/env php
<?php

/*
 * CLI-энтрипоинт финального свипа: `composer test:sweep [-- --days=N]`.
 *
 * Зачем отдельно от phpunit: свип нужен ровно тогда, когда phpunit до
 * teardown'а не дошёл — фатал, Ctrl-C, оборванный процесс. Запускать уборку
 * тем же механизмом, который только что не отработал, смысла нет, поэтому
 * здесь поднимается голое Laravel-приложение через testbench, без сьюта.
 *
 * Аккаунт и виджет захардкожены намеренно: свип бьёт в БОЕВОЙ аккаунт, и
 * возможность передать другой aId параметром — это возможность промахнуться
 * аккаунтом на чужих данных. Нужен другой аккаунт — правится здесь, осознанно.
 */

use mttzzz\AmoClient\AmoClientOctane;
use mttzzz\AmoClient\Tests\Support\AmoTestSweeper;
use Orchestra\Testbench\Foundation\Application;

require __DIR__.'/../vendor/autoload.php';

const SWEEP_ACCOUNT_ID = 16117840;
const SWEEP_CLIENT_ID = '00a140c1-7c52-4563-8b36-03f23754d255';

$usage = <<<'TXT'
Свип тестовых сущностей в amo.

  composer test:sweep                 окно 3 суток (умолчание)
  composer test:sweep -- --days=30    шире окно: ловит хвосты старых прогонов
  composer test:sweep -- --dry-run    поднять клиента и выйти, не трогая amo

Окно влияет только на типы без полнотекстового поиска (tasks/notes/calls) —
они ищутся фильтром по updated_at. leads/contacts/companies/catalogs/webhooks/
sources ищутся по маркеру и от окна не зависят.

TXT;

$days = 3;
$dryRun = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '-h') {
        echo $usage;
        exit(0);
    }

    /*
     * --dry-run проверяет ровно то, что ломается чаще всего и молча:
     * бутстрап приложения и доступ к локальной копии прод-БД за токеном.
     * Конструктор AmoClientOctane в amo не ходит вовсе, поэтому такой прогон
     * не тратит ни одного запроса боевого аккаунта.
     */
    if ($arg === '--dry-run') {
        $dryRun = true;

        continue;
    }

    if (preg_match('/^--days=(\d+)$/', $arg, $matches) === 1) {
        $days = max(1, (int) $matches[1]);

        continue;
    }

    fwrite(STDERR, "Неизвестный аргумент: {$arg}".PHP_EOL.PHP_EOL.$usage);
    exit(2);
}

$app = Application::create();

/*
 * Конфиг локальной копии прод-БД — вторая копия того, что лежит в
 * tests/BaseAmoClient.php::getEnvironmentSetUp. Копия существует потому, что
 * CLI живёт вне phpunit и не наследует базовый TestCase. Меняете реквизиты —
 * меняйте в обоих местах; переменные окружения ниже дают возможность
 * разойтись, не трогая код.
 */
$app['config']->set('database.default', 'octane');
$app['config']->set('database.connections.octane', [
    'driver' => 'pgsql',
    'host' => getenv('AMOCLIENT_DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('AMOCLIENT_DB_PORT') ?: 5432),
    'database' => getenv('AMOCLIENT_DB_DATABASE') ?: 'octane_pushka_biz',
    'username' => getenv('AMOCLIENT_DB_USERNAME') ?: 'mttzzzz',
    'password' => getenv('AMOCLIENT_DB_PASSWORD') ?: '',
    'charset' => 'utf8',
    'search_path' => 'public',
    'sslmode' => 'prefer',
]);

/* Те же значения, что у тестов: без прокси, без проверки сертификата, ретраи короткие. */
$app['config']->set('amoclient.proxies', [null]);
$app['config']->set('amoclient.verify', false);
$app['config']->set('amoclient.timeout', 60);
$app['config']->set('amoclient.connectTimeout', 10);
$app['config']->set('amoclient.retries', 2);
$app['config']->set('amoclient.retryDelay', 1000);

try {
    $amo = new AmoClientOctane(SWEEP_ACCOUNT_ID, SWEEP_CLIENT_ID);
} catch (Throwable $e) {
    fwrite(STDERR, 'Клиент amo не поднялся: '.$e->getMessage().PHP_EOL);
    fwrite(STDERR, 'Обычно это мёртвая локальная копия прод-БД — освежить: cd ~/projects/octane.pushka.biz && pgsync sync'.PHP_EOL);
    exit(1);
}

if ($dryRun) {
    echo 'Клиент поднят: аккаунт '.$amo->accountId.', маркер '.AmoTestSweeper::TEST_MARKER.', окно '.$days.' сут.'.PHP_EOL;
    echo 'Запросов в amo не сделано (--dry-run).'.PHP_EOL;
    exit(0);
}

$report = (new AmoTestSweeper($days))->sweep($amo);

echo AmoTestSweeper::render($report);

/*
 * Ненулевой код возврата на failed/refused: свип — сетка, и её молчаливое
 * «ну не смогло» ровно то, ради чего сетку ставили. Warnings кодом не
 * поднимаем — это состояние аккаунта (отключённые покупатели, упёршийся в
 * потолок скан), а не поломка уборки.
 */
exit($report['failed'] === [] && $report['refused'] === [] ? 0 : 1);
