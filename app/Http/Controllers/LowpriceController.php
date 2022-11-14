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
            //$testRow
        );
        return response($insertResponse->failedRows());
    }
}
