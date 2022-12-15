<?php

namespace App\Helpers;

use App\Services\ConfigurationProvider\ConfigurationProvider;
use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Support\Facades\Log;

class TestBigQuery
{
    private $bigQuery;

    public function __construct() {
        $json = ConfigurationProvider::getJson();
        $this->bigQuery = new BigQueryClient([
            'projectId' => 'saaslowprices',
            'keyFilePath' => $json
        ]);
    }

    public function populateDb() {
        $bigQuery = $this->bigQuery;
        $today = date('Y-m-d');
        Log::info($today);
        try {
            $this->cleanDb($bigQuery);
        }
        catch (\Exception $e) {
            Log::info($e);
        }
        $products = [];
        $prices = [];
        for ($i=0; $i<5; $i++) {
            if ($i == 0 or $i == 3 or $i == 4) $date = date('Y-m-d',strtotime('- 25 day'));
            else $date = null;
            $products[$i] = ['data' => ['productId' => $i, 'merchantId' => 'aaaa', 'countryCode' => 'FR', 'promotionDate' => $date]];
        }
        for ($i=5; $i<10; $i++) {
            if ($i==5 or $i==8 or $i==9) $date = date('Y-m-d',strtotime('- 25 day'));
            else $date = null;
            $products[$i] = ['data' => ['productId' => $i, 'merchantId' => 'bbbb', 'countryCode' => 'FR', 'promotionDate' => $date]];
        }

        $i=0;
        foreach ($products as $product) {
            $day = 60;
            $productId = $product['data']['productId'];
            $merchantId = $product['data']['merchantId'];
            $countryCode = 'FR';
            do {
                $date = date('Y-m-d', strtotime('- '.$day.' day'));
                switch ($productId) {
                    case 0 :
                    case 5:
                        $price = 1000-(500 - $day);
                        if ($day > 25) $salePrice = null;
                        else $salePrice = $price-(499 - $day);
                        break;
                    case 1 :
                    case 6:
                        $price = 1000-(500 - $day);
                        $salePrice = null;
                        break;
                    case 2 :
                    case 7:
                        $price = 1000 + (500-$day);
                        $salePrice = null;
                        break;
                    case 3 :
                    case 8:
                        $price = 1000-(500 + $day);
                        if ($day > 25) $salePrice = null;
                        else $salePrice = $price-(499 - $day);
                        break;
                    case 4 :
                    case 9:
                        $price = 1000-(500 - $day);
                        if ($day > 25 and $day < 30) $salePrice = null;
                        elseif($day<=25) $salePrice = $price-(499 - $day);
                        else $salePrice = $price-(499 - $day);
                        break;

                }
                $prices[$i.$day] = ['data'=>['date' => $date, 'productId' => $productId, 'merchantId' => $merchantId, 'countryCode' => $countryCode, 'price' => $price, 'salePrice' => $salePrice]];
                $day--;
            } while ($day != -1);
            $i++;
        }


        $dataset = $bigQuery->dataset('lowprice');
        $table = $dataset->table('product_test');
        $insertResponse = $table->insertRows(
            $products
        );

        $dataset = $bigQuery->dataset('lowprice');
        $table = $dataset->table('price_test');
        $insertResponse = $table->insertRows(
            $prices
        );
    }

    public function cleanDb() {
        $deleteQuery = "DELETE
                        FROM
                          lowprice.product_test
                        WHERE
                          productId LIKE '%';
                        DELETE
                        FROM
                          lowprice.price_test
                        WHERE
                          productId LIKE '%';";

        $queryJobConfig = $this->bigQuery->query($deleteQuery);
        $queryResults = $this->bigQuery->runQuery($queryJobConfig);
    }

    public function getData($merchant_id) {
        $query = "SELECT productId, minPrice, date FROM lowprice.view_test_minPrices WHERE merchantId like(@merchant_id) AND countryCode like(@country_code)";
        $queryJobConfig = $this->bigQuery->query($query)
            ->parameters([
                'merchant_id' => $merchant_id,
                'country_code'=>'FR'
            ]);
        $queryResults = $this->bigQuery->runQuery($queryJobConfig);
        $result = [];
        foreach ($queryResults as $row) {
            $date = $row['date']->formatAsString();
            $min_price = $row['minPrice'];
            $productId=$row['productId'];
            $result[] = ['productId' => $productId, 'minPrice' => $min_price, 'date' => $date];
        }
        return $result;
    }
}
