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
        $queryResults = $bigQuery->runQuery($queryJobConfig);
        $csv = [];
        foreach ($queryResults as $row) {
            $date = $row['date']->formatAsString();
            $min_price = $row['minPrice'];
            $productId=$row['productId'];
            $csv[] = [$productId, $min_price, $date];
        }
        return $csv;
    }
}
