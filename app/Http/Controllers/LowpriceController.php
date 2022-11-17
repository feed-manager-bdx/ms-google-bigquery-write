<?php

namespace App\Http\Controllers;

use App\Services\ConfigurationProvider\ConfigurationProvider;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use Google\Cloud\BigQuery\BigQueryClient;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;


class LowpriceController extends Controller
{
    public function postToBigQuery(Request $request) {
        $products = $request->json()->get('products');
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

        $fileName = $merchant_id.'_'.time().".csv";
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

        $bucket = $storage->bucket('lowpricecsv');
        $bucket->upload(
            fopen($fileName, 'r')
        );
        unlink($fileName);

        $dlLink = $bucket->object($fileName)->signedUrl(new \DateTime('tomorrow'));

        return response($dlLink);
    }
}
