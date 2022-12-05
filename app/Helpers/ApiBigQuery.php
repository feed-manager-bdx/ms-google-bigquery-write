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
use Google_Client;
use Google_Exception;
use Google_Service_ShoppingContent;
use Illuminate\Database\Eloquent\Model;
use App\Models;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DateTime;

class ApiBigQuery extends Model
{
    public function bigQuery($products)
    {
        if ($products == []) return 0;

        for ($i=0; $i<sizeof($products); $i++) {
            $products[$i]=['data'=>$products[$i]];
        }
        $json = ConfigurationProvider::getJson();
        $bigQuery = new BigQueryClient([
            'projectId' => 'saaslowprices',
            'keyFilePath' => $json
        ]);
        $dataset = $bigQuery->dataset('lowprice');
        $table = $dataset->table('productsPrices');
        $insertResponse = $table->insertRows(
            $products
        );

        return 1;
    }
}
