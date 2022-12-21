<?php

namespace App\Http\Controllers;

use App\Helpers\ApiBigQuery;
use App\Helpers\ApiGoogleStorage;
use App\Services\ConfigurationProvider\ConfigurationProvider;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

/**
 * @OA\Server(url="http://localhost:8084/api")
 * @OA\Server(url="http://vps-da63b0f9.vps.ovh.net/api")
 */

class LowpriceController extends Controller
{
    private ApiBigQuery $apiBigQuery;
    private ApiGoogleStorage $apiGoogleStorage;

    public function __construct() {
        $this->apiBigQuery = new ApiBigQuery();
        $this->apiGoogleStorage = new ApiGoogleStorage();
    }

    /**
     * @OA\Post(
     *     path="/productsPrices",
     *     tags={"productsPrices BigQuery"},
     *     summary="Posts products and prices to BigQuery",
     *      @OA\Parameter(
     *          name="X-Timestamp",
     *          in="header",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="X-Authorization",
     *          description="Authorization key",
     *          in="header",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="Content-Type",
     *          in="header",
     *          required=true,
     *      ),
     *      @OA\Parameter(
     *          name="ProjectId",
     *          description="Token access",
     *          in="header",
     *          required=true,
     *      ),
     *      @OA\RequestBody(
     *        required = true,
     *        description = "Products and Prices",
     *        @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                property="products",
     *                type="array",
     *                @OA\Items(
     *                      @OA\Property(
     *                         property="price",
     *                         type="array",
     *                         @OA\Items(
     *                               @OA\Property(
     *                               property="productId",
     *                               type="string"
     *                               ),
     *                               @OA\Property(
     *                               property="merchantId",
     *                               type="string"
     *                               ),
     *                               @OA\Property(
     *                               property="countryCode",
     *                               type="string"
     *                               ),
     *                               @OA\Property(
     *                               property="price",
     *                               type="float"
     *                               ),
     *                               @OA\Property(
     *                               property="salePrice",
     *                               type="float"
     *                               ),
     *                               @OA\Property(
     *                               property="date",
     *                               type="date"
     *                               )
     *                         )
     *                      ),
     *                      @OA\Property(
     *                         property="product",
     *                         type="array",
     *                         @OA\Items(
     *                               @OA\Property(
     *                               property="productId",
     *                               type="string"
     *                               ),
     *                               @OA\Property(
     *                               property="merchantId",
     *                               type="string"
     *                               ),
     *                               @OA\Property(
     *                               property="countryCode",
     *                               type="string"
     *                               ),
     *                               @OA\Property(
     *                               property="promotionDate",
     *                               type="date"
     *                               ),
     *                         )
     *                      ),
     *                ),
     *             ),
     *        ),
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Products posted with success",
     *       ),
     * )
     */
    public function productsPrices(Request $request) {
        $products = $request->json()->get('products');
        $response = $this->apiBigQuery->bigQuery($products);

        return response($response);
    }

    /**
     * @OA\Get(
     *     path="/productsPricesCsv/{merchantId}",
     *     tags={"productsPrices Csv"},
     *     summary="Fetch from BigQuery min prices per products",
     *      @OA\Parameter(
     *          name="X-Timestamp",
     *          in="header",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="X-Authorization",
     *          description="Authorization key",
     *          in="header",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="Content-Type",
     *          in="header",
     *          required=true,
     *      ),
     *      @OA\Parameter(
     *          name="ProjectId",
     *          description="Token access",
     *          in="header",
     *          required=true,
     *      ),
     *      @OA\Parameter(
     *          name="merchantId",
     *          in="path",
     *          required=true,
     *      ),
     *      @OA\Parameter(
     *          name="code",
     *          in="query",
     *          required=true,
     *      ),
     *     @OA\Response(
     *          response=200,
     *          description="Products with min prices",
     *          @OA\JsonContent(
     *                      @OA\Property(
     *                         type="array",
     *                         @OA\Items(
     *                               @OA\Property(
     *                               property="productId",
     *                               type="string"
     *                               ),
     *                               @OA\Property(
     *                               property="minPrice",
     *                               type="float"
     *                               ),
     *                               @OA\Property(
     *                               property="date",
     *                               type="date"
     *                               ),
     *                         )
     *                      ),
     *          ),
     *       ),
     * )
     */
    public function productPricesCsv(Request $request) {
        $merchant_id=$request->route('merchantId');
        $country_code=$request->query->get('code');
        $return = $this->apiGoogleStorage->googleStorage($merchant_id, $country_code);

        return response($return);
    }


    /**
     * @OA\Get(
     *     path="/latestPrices/{merchantId}",
     *     tags={"productPrices Latest"},
     *     summary="Fetch from BigQuery last entry per product",
     *      @OA\Parameter(
     *          name="X-Timestamp",
     *          in="header",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="X-Authorization",
     *          description="Authorization key",
     *          in="header",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="Content-Type",
     *          in="header",
     *          required=true,
     *      ),
     *      @OA\Parameter(
     *          name="ProjectId",
     *          description="Token access",
     *          in="header",
     *          required=true,
     *      ),
     *      @OA\Parameter(
     *          name="merchantId",
     *          in="path",
     *          required=true,
     *      ),
     *      @OA\Parameter(
     *          name="code",
     *          in="query",
     *          required=true,
     *      ),
     *     @OA\Response(
     *          response=200,
     *          description="Products with min prices",
     *          @OA\JsonContent(
     *                      @OA\Property(
     *                         type="array",
     *                         @OA\Items(
     *                               @OA\Property(
     *                               property="productId",
     *                               type="string"
     *                               ),
     *                               @OA\Property(
     *                               property="merchantId",
     *                               type="string"
     *                               ),
     *                               @OA\Property(
     *                               property="countryCode",
     *                               type="string"
     *                               ),
     *                               @OA\Property(
     *                               property="price",
     *                               type="float"
     *                               ),
     *                               @OA\Property(
     *                               property="salePrice",
     *                               type="float"
     *                               ),
     *                               @OA\Property(
     *                               property="promotionDate",
     *                               type="date"
     *                               ),
     *                         )
     *                      ),
     *          ),
     *       ),
     * )
     */
    public function latestPrices(Request $request) {
        $merchant_id=$request->route('merchantId');
        $country_code=$request->query->get('code');
        Log::info($country_code);
        $return = $this->apiGoogleStorage->latestPrices($merchant_id, $country_code);

        return response($return);
    }
}
