<?php
/**
 * @Date: 03/06/16
 * @package    Feed Manager
 * @author     guillaume court <infogco33@gmail.com>
 * @version    1.0
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed merchant_id
 * @property mixed json_auth
 * @property mixed id
 * @method static find($customer_id)
 */
class Customer extends Model
{
    const TABLE_NAME = 'customers';

    /**
     * @return mixed
     */public function getJsonAuth(): mixed
    {
        return $this->json_auth;
    }
}
