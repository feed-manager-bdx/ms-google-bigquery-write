<?php
/**
 * @Date: 03/06/16
 * @package    Feed Manager
 * @author     guillaume court <infogco33@gmail.com>
 * @version    1.0
 */

namespace App\Helpers;

use App\Models\Customer;
use App\Services\ConfigurationProvider\ConfigurationProvider;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\Date;
use Google\Cloud\Storage\StorageClient;
use Google_Client;
use Google_Exception;
use Google_Service_ShoppingContent;
use Illuminate\Database\Eloquent\Model;
use App\Models;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DateTime;

class ApiGoogleStorage extends Model
{
    public function googleStorage($merchant_id, $country_code)
    {
        $json = ConfigurationProvider::getJson();
        $bigQuery = new BigQueryClient([
            'projectId' => 'saaslowprices',
            'keyFilePath' => $json
        ]);
        $query = "SELECT productId, minPrice, date FROM lowprice.view_minPrices WHERE merchantId like(@merchant_id) AND countryCode like(@country_code)";
        $queryJobConfig = $bigQuery->query($query)
            ->parameters([
                'merchant_id' => $merchant_id,
                'country_code'=>$country_code
            ]);
        $queryResults = $bigQuery->runQuery($queryJobConfig, ['maxResults'=>5000]);
        $isComplete = $queryResults->isComplete();
        $csv = [];
        if ($isComplete) {
            $rows = $queryResults->rows();
            foreach ($rows as $row) {
                $date = $row['date']->formatAsString();
                $min_price = $row['minPrice'];
                $productId=$row['productId'];
                $csv[] = [$productId, $min_price, $date];
            }
        }
        Log::info('CSV size for '.$merchant_id.$country_code.' : '.sizeof($csv));
        $this->uploadCsv($merchant_id, $country_code, $csv);
        return $csv;
    }

    public function uploadCsv($merchant_id, $country_code, $products) {
        Log::info('Posting CSV for '.$merchant_id.$country_code);
        $json = ConfigurationProvider::getJson();
        $csv = $products ?? [];
        $fileName = $merchant_id.'-'.$country_code.".csv";
        $storagePath = storage_path('/app/public/csv/');

        if (!is_dir($storagePath)) {
            mkdir($storagePath);
        }

        $file = fopen($storagePath.$fileName,"w");
        fputcsv($file, ['product_id', 'min_price', 'date'],';');
        foreach ($csv as $line) {
            fputcsv($file, $line, ';');
        }
        fclose($file);
        $storage = new StorageClient([
            'projectId' => 'saaslowprices',
            'keyFilePath' => $json
        ]);
        $bucketName = 'lowpricecsv';
        $bucket = $storage->bucket($bucketName);
        $resultBucket = $bucket->upload(
            fopen($storagePath.$fileName, 'r'),
            [
                'predefinedAcl' => 'publicRead'
            ]
        );

        if(is_file($storagePath.$fileName)) {
            unlink($storagePath.$fileName);
        }
    }

    public function latestPrices($merchant_id, $country_code) {
        $json = ConfigurationProvider::getJson();
        $bigQuery = new BigQueryClient([
            'projectId' => 'saaslowprices',
            'keyFilePath' => $json
        ]);
        $query = "SELECT * FROM lowprice.view_latestsProductsPrices WHERE merchantId like(@merchant_id) AND countryCode like(@country_code)";
        $queryJobConfig = $bigQuery->query($query)
            ->parameters([
                'merchant_id' => $merchant_id,
                'country_code'=>$country_code
            ]);
        $queryResults = $bigQuery->runQuery($queryJobConfig, ['maxResults'=>5000]);
        $products = [];
        $isComplete = $queryResults->isComplete();

        if ($isComplete) {
            $rows = $queryResults->rows();
            foreach ($rows as $row) {
                $promotionDate = $row['promotionDate'] == null ? null : $row['promotionDate']->formatAsString();
                $price = $row['price'];
                $merchantId = $row['merchantId'];
                $salePrice = $row['salePrice'];
                $countryCode=$row['countryCode'];
                $productId=$row['productId'];
                $products[] = ["productId" => $productId, "price" => $price, "promotionDate" => $promotionDate, "merchantId" => $merchantId, "salePrice" => $salePrice, "countryCode" => $countryCode];
            }
        }
        Log::info('Previous Products size for '.$merchant_id.$country_code.' : '.sizeof($products));
        return $products;
    }

    public function latestPricesTest() {
        $json = ConfigurationProvider::getJson();
        $bigQuery = new BigQueryClient([
            'projectId' => 'saaslowprices',
            'keyFilePath' => $json
        ]);
        $query = "SELECT productId, minPrice, date FROM lowprice.view_minPrices WHERE merchantId like(@merchant_id) AND countryCode like(@country_code)";
        $queryJobConfig = $bigQuery->query($query)
            ->parameters([
                'merchant_id' => '7470747',
                'country_code'=>'FR'
            ]);
        $queryResults = $bigQuery->runQuery($queryJobConfig, ['maxResults'=>5000]);
        Log::info(($queryResults->info()));
        $isComplete = $queryResults->isComplete();
        $csv = [];
        if ($isComplete) {
            $rows = $queryResults->rows();
            foreach ($rows as $row) {
                $date = $row['date']->formatAsString();
                $min_price = $row['minPrice'];
                $productId=$row['productId'];
                $csv[] = [$productId, $min_price, $date];
            }
        }
        return $csv;

    }
}
