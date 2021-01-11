<?php
/**
 * Knawat MP API Wrapper.
 *
 * @since      1.0.0
 * @author     Dharmesh Patel
 * @package    Knawat
 */

namespace Knawat\Dropshipping;

use Knawat\Dropshipping\HttpClient;

/**
 * Knawat MP API class.
 *
 * @since      1.0.0
 * @category   Class
 * @package    Knawat
 */
class MP
{

    /**
     * Contain Knawat API URL
     * @access private
     */
    private $api_url = 'https://mp.knawat.io/api';

    /**
     * Contain client
     * @access private
     */
    private $client;

    /**
     * Initialize Knawat MP API.
     *
     * @param string $consumer_key    Consumer key.
     * @param string $consumer_secret Consumer secret.
     * @param array  $options         Options.
     */
    public function __construct($consumer_key, $consumer_secret, $options = array())
    {
        $this->client = new HttpClient($this->api_url, $consumer_key, $consumer_secret, $options);
    }

    /**
     * Get Knawat MP Access Token
     *
     * @return string $access_token
     */
    public function getAccessToken()
    {
        return $this->client->getAccessToken();
    }

    /**
     * Get Knawat Products.
     *
     * @param int    $limit Product Limit
     * @param int    $page  Page
     * @param string $last_update UTC Timestamp
     * @return object Knawat Products
     */
    public function getProducts($limit = 25, $page = 1, $last_update = '')
    {
        $path = '/catalog/products?limit='.$limit.'&page='.$page;
        if (!empty($last_update)) {
            $path .= '&lastupdate='.$last_update;
        }
        return $this->client->get($path);
    }

    /**
     * Get Knawat Product By SKU
     *
     * @param string $sku
     * @return object Knawat Product Object
     */
    public function getProductBySku($sku)
    {
        $path = '/catalog/products/'.$sku;
        return $this->client->get($path);
    }

    /**
     * Get Knawat Orders.
     *
     * @param int    $limit Order Limit
     * @param int    $page  Page
     * @return object Knawat Orders
     */
    public function getOrders($limit = 10, $page = 1)
    {
        $path = '/orders?limit='.$limit.'&page='.$page;
        return $this->client->get($path);
    }

    /**
     * Get Knawat Order By ID
     *
     * @param string $orderId
     * @return object Knawat Product Object
     */
    public function getOrderById($orderId)
    {
        $path = '/orders/'.$orderId;
        return $this->client->get($path);
    }

    /**
     * Create Sales Order on Knawat API
     *
     * @param array $data
     * @return object
     */
    public function createOrder($data)
    {
        $path = '/orders';
        return $this->client->post($path, $data);
    }

    /**
     * Update Sales Order on Knawat API by Order ID
     *
     * @param string $order_id
     * @param array  $data
     * @return object
     */
    public function updateOrder($order_id, $data)
    {
        $path = '/orders/'.$order_id;
        return $this->client->put($path, $data);
    }

    /**
    * get function.
    *
    * Performs an API GET request
    *
    * @access public
    * @return object
    */
    public function get($path, $return_array = false)
    {
        return $this->client->get($path, $return_array);
    }


    /**
    * post function.
    *
    * Performs an API POST request
    *
    * @access public
    * @return object
    */
    public function post($path, $data = array(), $return_array = false)
    {
        return $this->client->post($path, $data, $return_array);
    }


    /**
    * put function.
    *
    * Performs an API PUT request
    *
    * @access public
    * @return object
    */
    public function put($path, $data = array(), $return_array = false)
    {
        return $this->client->put($path, $data, $return_array);
    }

    /**
    * DELETE function.
    *
    * Performs an API DELETE request
    *
    * @access public
    * @return object
    */
    public function delete($path, $data = array(), $return_array = false)
    {
        return $this->client->delete($path, $data, $return_array);
    }
}
