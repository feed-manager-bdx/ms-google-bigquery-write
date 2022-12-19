<?php

namespace App\Http\Controllers;

use App\Helpers\ApiBigQuery;
use App\Helpers\ApiGoogleStorage;
use App\Services\ConfigurationProvider\ConfigurationProvider;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Support\Facades\Log;

class LowpriceController extends Controller
{
    private ApiBigQuery $apiBigQuery;
    private ApiGoogleStorage $apiGoogleStorage;

    public function __construct() {
        $this->apiBigQuery = new ApiBigQuery();
        $this->apiGoogleStorage = new ApiGoogleStorage();
    }

    public function productsPrices(Request $request) {
        $products = $request->json()->get('products');
        $response = $this->apiBigQuery->bigQuery($products);

        return response($response);
    }

    public function productPricesCsv(Request $request) {
        $merchant_id=$request->route('merchantId');
        $country_code=$request->query->get('countryCode');
        $return = $this->apiGoogleStorage->googleStorage($merchant_id, $country_code);

        return response($return);
    }

    public function latestPrices(Request $request) {
        $merchant_id=$request->route('merchantId');
        $country_code=$request->query->get('countryCode');
        Log::info($country_code);
        $return = $this->apiGoogleStorage->latestPrices($merchant_id, $country_code);

        return response($return);
    }
}
