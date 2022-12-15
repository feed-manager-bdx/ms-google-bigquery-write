<?php

namespace Tests\Feature;

use App\Helpers\TestBigQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MinpriceTest extends TestCase
{
    public function test_product_0()
    {
        $helper = new TestBigQuery();
        $helper->populateDb();
        $products=$helper->getData('aaaa');

        Log::info($products);

        $this->assertEquals(date('Y-m-d',strtotime('- 26 day')), $products[0]['date']);
        $this->assertEquals(date('Y-m-d',strtotime('- 1 day')), $products[1]['date']);
        $this->assertEquals(date('Y-m-d',strtotime('- 30 day')), $products[2]['date']);
        $this->assertEquals(date('Y-m-d',strtotime('- 55 day')), $products[3]['date']);
        $this->assertEquals(date('Y-m-d',strtotime('- 30 day')), $products[4]['date']);
    }

}
