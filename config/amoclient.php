<?php

return [
    'proxies' =>  [
        null,                              // Первый запрос делаем без прокси, напрямую
        // 'http://1.1.1.1:12345',         // Прокси 1
        // 'http://2.2.2.2:12345',         // Прокси 2
    ],
    'timeout' => 60,                       // Maximum number of seconds to wait for a response
    'connectTimeout' => 10,                // Maximum number of seconds to wait while trying to connect to a server
    'retries' => 2,                        // количество попыток.  Если у вас 3 прокси, то при 'retries' => 2, будет совершено 6 запросов (2 * (1 + 2))
    'retryDelay' => 1000,                  //Задержка перед повтором в миллисекундах
];
