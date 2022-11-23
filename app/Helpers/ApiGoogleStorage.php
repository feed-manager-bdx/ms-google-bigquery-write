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
    public function googleStorage($merchant_id)
    {
        $json = ConfigurationProvider::getJson();
        $bigQuery = new BigQueryClient([
            'projectId' => 'saaslowprices',
            'keyFilePath' => $json
        ]);
        $query = "SELECT productId, minPrice FROM lowprice.view_minPrices WHERE merchantId like(@merchant_id)";
        $queryJobConfig = $bigQuery->query($query)
            ->parameters([
                'merchant_id' => $merchant_id
            ]);
        $queryResults = $bigQuery->runQuery($queryJobConfig);
        $csv = [];
        foreach ($queryResults as $row) {
            $csv[] = $row;
        }
        $fileName = $merchant_id.".csv";
        $file = fopen($fileName,"w");
        fputcsv($file, ['product_id', 'minPrice'],';');
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
        $bucket->upload(
            fopen($fileName, 'r'),
            [
                'predefinedAcl' => 'publicRead'
            ]
        );
        unlink($fileName);

        return 'https://storage.googleapis.com/'.$bucketName.'/'.$fileName;
    }
}
