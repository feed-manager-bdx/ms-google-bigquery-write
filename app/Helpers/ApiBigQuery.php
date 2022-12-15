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
    public function bigQuery($products) {
        $json = ConfigurationProvider::getJson();
        $bigQuery = new BigQueryClient([
            'projectId' => 'saaslowprices',
            'keyFilePath' => $json
        ]);

        $deleteQuery = "DELETE
                        FROM
                          lowprice.product_temp
                        WHERE
                          productId LIKE '%'
                          AND _PARTITIONTIME < TIMESTAMP_SUB(CURRENT_TIMESTAMP(), INTERVAL 91 MINUTE);";

        $queryJobConfig = $bigQuery->query($deleteQuery);
        $queryResults = $bigQuery->runQuery($queryJobConfig);

        if ($products == []) return 0;
        $mergeQuery = "MERGE
                          lowprice.product p
                        USING
                          (
                          SELECT
                            *
                          FROM (
                            SELECT
                              merchantId,
                              productId,
                              countryCode,
                              promotionDate,
                              ROW_NUMBER() OVER(PARTITION BY merchantId, productId, countryCode ORDER BY _PARTITIONTIME DESC ) AS rowno
                            FROM
                              lowprice.product_temp)
                          WHERE
                            rowno = 1) pt
                        ON
                          p.merchantId = pt.merchantId
                          AND p.productId=pt.productId
                          AND p.countryCode=pt.countryCode
                          WHEN MATCHED THEN UPDATE SET p.promotionDate = pt.promotionDate
                          WHEN NOT MATCHED
                          THEN
                        INSERT
                          (merchantId,
                            productId,
                            countryCode,
                            promotionDate)
                        VALUES
                          (pt.merchantId, pt.productId, pt.countryCode, pt.promotionDate)";

        $prices = [];
        $productsBq = [];
        for ($i = 0; $i < sizeof($products); $i++) {
            $prices[$i] = ['data' => $products[$i]['price']];
            $productsBq[$i] = ['data' => $products[$i]['product']];
        }

        $dataset = $bigQuery->dataset('lowprice');
        $table = $dataset->table('price');
        $insertResponse = $table->insertRows(
            $prices
        );

        $table = $dataset->table('product_temp');
        $insertResponse = $table->insertRows(
            $productsBq
        );

        $queryJobConfig = $bigQuery->query($mergeQuery);
        $queryResults = $bigQuery->runQuery($queryJobConfig);

        return 1;
    }
}
