<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Google_Exception;
use Google_Service_ShoppingContent;
use Google\Cloud\BigQuery\BigQueryClient;
use App\Models\Customer;
use App\Helpers\ApiShopping;


class LowpriceController extends Controller
{
    public function lowPrice(Request $request)
    {
        $apiShopping = new ApiShopping();
        $customer = Customer::find(1);
        $this->toBigQuery($apiShopping->getProductsPrices($customer));
    }
    public function toBigQuery($products) {
        dd($products);
        $json = public_path();
        $json.="/saaslowprices-22c63e1a3961.json";
        $bigQuery = new BigQueryClient([
            'projectId' => 'saaslowprices',
            'keyFilePath' => $json
        ]);
        $dataset = $bigQuery->dataset('lowprice');
        $table = $dataset->table('productsPrices');
        $insertResponse = $table->insertRows([
            ['data' => $products],
        ]);
        dd($insertResponse);
    }

    public function googleLogIn() {
        return view('login', []);
    }
}
