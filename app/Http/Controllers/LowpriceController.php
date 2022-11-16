<?php

namespace App\Http\Controllers;

use App\Services\ConfigurationProvider\ConfigurationProvider;
use Illuminate\Http\Request;
use Google\Cloud\BigQuery\BigQueryClient;
use App\Models\Customer;
use App\Helpers\ApiShopping;
use Illuminate\Support\Facades\Log;


class LowpriceController extends Controller
{
    public function postToBigQuery(Request $request) {
        $products = $request->json()->get('products');
        //$products = json_decode($products,true);
        $testRow= [
            ['data'=> ['merchantId'=>1, 'productId'=>1]]
        ];
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
        return response($insertResponse->failedRows());
    }

    public function getProductsPrices(Request $request) {
        $json = ConfigurationProvider::getJson();
        $merchant_id=$request->json()->get('merchant_id');
        $bigQuery = new BigQueryClient([
            'projectId' => 'saaslowprices',
            'keyFilePath' => $json
        ]);

        $query = "SELECT productId, minPrice FROM lowprice.view_minPrices WHERE merchantId like(@merchant_id) limit 10";
        $queryJobConfig = $bigQuery->query($query)
            ->parameters([
                'merchant_id' => $merchant_id
            ]);

        $queryResults = $bigQuery->runQuery($queryJobConfig);
        $csv = [];
        foreach ($queryResults as $row) {
            $csv[] = $row;
        }

        return response(json_encode($csv));
    }
}
