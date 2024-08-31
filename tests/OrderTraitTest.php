<?php

namespace mttzzz\AmoClient\Tests;

class OrderTraitTest extends BaseAmoClient
{
    public function testOrderByCreatedAtAsc()
    {
        $leads = $this->amoClient->leads->orderByCreatedAtAsc()->limit(10)->get();
        $this->assertIsArray($leads);
        $updatedAts = array_column($leads, 'created_at');
        $sortedUpdatedAtsAsc = $updatedAts;
        $this->assertEquals($sortedUpdatedAtsAsc, $updatedAts);
    }

    public function testOrderByCreatedAtDesc()
    {
        $leads = $this->amoClient->leads->orderByCreatedAtDesc()->limit(10)->get();
        $this->assertIsArray($leads);
        $updatedAts = array_column($leads, 'created_at');
        $sortedUpdatedAtsDesc = $updatedAts;
        rsort($sortedUpdatedAtsDesc);
        $this->assertEquals($sortedUpdatedAtsDesc, $updatedAts);
    }

    public function testOrderByUpdatedAtAsc()
    {
        $leads = $this->amoClient->leads->orderByUpdatedAtAsc()->limit(10)->get();
        $this->assertIsArray($leads);
        $updatedAts = array_column($leads, 'updated_at');
        $sortedUpdatedAtsAsc = $updatedAts;
        sort($sortedUpdatedAtsAsc);
        $this->assertEquals($sortedUpdatedAtsAsc, $updatedAts);
    }

    public function testOrderByUpdatedAtDesc()
    {
        $leads = $this->amoClient->leads->orderByUpdatedAtDesc()->limit(10)->get();
        $this->assertIsArray($leads);
        $updatedAts = array_column($leads, 'updated_at');
        $sortedUpdatedAtsDesc = $updatedAts;
        rsort($sortedUpdatedAtsDesc);
        $this->assertEquals($sortedUpdatedAtsDesc, $updatedAts);
    }

    public function testOrderByIdAsc()
    {
        $leads = $this->amoClient->leads->orderByIdAsc()->limit(10)->get();
        $this->assertIsArray($leads);
        $ids = array_column($leads, 'id');
        $sortedIdsAsc = $ids;
        sort($sortedIdsAsc);
        $this->assertEquals($sortedIdsAsc, $ids);
    }

    public function testOrderByIdDesc()
    {
        $leads = $this->amoClient->leads->orderByIdDesc()->limit(10)->get();
        $this->assertIsArray($leads);
        $ids = array_column($leads, 'id');
        $sortedIdsDesc = $ids;
        rsort($sortedIdsDesc);
        $this->assertEquals($sortedIdsDesc, $ids);
    }
}
