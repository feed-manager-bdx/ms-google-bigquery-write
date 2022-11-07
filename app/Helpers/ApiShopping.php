<?php
/**
 * @Date: 03/06/16
 * @package    Feed Manager
 * @author     guillaume court <infogco33@gmail.com>
 * @version    1.0
 */

namespace App\Helpers;

use Google_Client;
use Google_Exception;
use Google_Service_ShoppingContent;
use Illuminate\Database\Eloquent\Model;
use App\Models;
use Illuminate\Support\Facades\Log;

class ApiShopping extends Model
{
    public function getDataFeedsStatuses(Models\Customer $customer)
    {
        $service = $this->_getServiceShoppingContent($customer);
        if ($service) {
            return $service->datafeedstatuses->listDatafeedstatuses($customer->merchant_id);
        }
        return [];
    }

    public function getDataFeeds(Models\Customer $customer): \Google_Service_ShoppingContent_DatafeedsListResponse
    {
        $service = $this->_getServiceShoppingContent($customer);
        if ($service) {
            return $service->datafeeds->listDatafeeds($customer->merchant_id);
        }
        return [];
    }

    public function getDataFeed(Models\Customer $customer, $feedId)
    {
        $service = $this->_getServiceShoppingContent($customer);
        if ($service) {
            return $service->datafeeds->get($customer->merchant_id, $feedId);
        }
        return [];
    }


    public function getAccountStatuses(Models\Customer $customer)
    {
        $service = $this->_getServiceShoppingContent($customer);
        if ($service) {
            return $service->accountstatuses->get(trim($customer->merchant_id), trim($customer->merchant_id));
        }
        return [];
    }

    /**
     * @param Models\Customer $customer
     * @return Google_Service_ShoppingContent|void
     */
    private function _getServiceShoppingContent(Models\Customer $customer)
    {
        Log::info($customer);
        Log::info($customer->merchant_id);
        $json = public_path();
        $json .='/saaslowprices-22c63e1a3961.json';
        try {
            $client = new Google_Client();
            $client->setAuthConfig($json);
            $client->setApplicationName("Saas Low Price");
            $client->setScopes(Google_Service_ShoppingContent::CONTENT);
            return new Google_Service_ShoppingContent($client);
        } catch (Google_Exception $e) {
            Log::error($customer);
            Log::error($customer->merchant_id);
            Log::error($e->getMessage());
        }
        return;
    }

    public function getProductsPrices(Models\Customer $customer)
    {
        try {

            $serviceShoppingContent = $this->_getServiceShoppingContent($customer);
            $products = $serviceShoppingContent->products->listProducts($customer->merchant_id);
            $toBigQuery = [];
            foreach ($products as $product) {
                $productId = $product->offerId;
                $merchantId = $customer->merchant_id;
                $countryCode = $product->feedLabel;
                $price = $product->price->value;
                $date = new \DateTime();
                $date = $date->format('Y-m-d');
                $object = ['productId' => $productId, 'merchantId' => $merchantId, 'countryCode' => $countryCode, 'price' => $price, 'date' => $date];
                $toBigQuery[] = $object;
            }
            return $toBigQuery;
        } catch (Google_Exception $e) {
            dd($e);
        }
    }
}
