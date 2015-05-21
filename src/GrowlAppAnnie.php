<?php
/*
 * A simple PHP class for App Annie API
 *
 * (c) Growl Media Ltd.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * App Annie API class
 */
class GrowlAppAnnie
{
    protected $apiKey;
    protected $apiUrl = 'https://api.appannie.com';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Retrieve the all account connections, available in an App Annie account
     * @link http://support.appannie.com/hc/en-us/articles/204208994-1-Account-Connections-List
     * @param array $params
     * @return array
     */
    public function getAccounts($params = array())
    {
        return $this->sendRequest('get', $this->getUrl('/v1.2/accounts', $params));
    }

    /**
     * Retrieve a single account connection’s sales information
     *
     * @link http://support.appannie.com/hc/en-us/articles/204208954-1-Account-Connection-Sales
     * @param string $accountId
     * @param array $params
     * @return array
     */
    public function getAccountSales($accountId, $params = array())
    {
        return $this->sendRequest('get', $this->getUrl("/v1.2/accounts/$accountId/sales", $params));
    }

    /**
     * Retrieve the sales data for a single app.
     *
     * @link http://support.appannie.com/hc/en-us/articles/204208914-3-Product-Sales-
     * @param string $accountId
     * @param string $appId
     * @param array $params
     * @return array
     */
    public function getAccountAppSales($accountId, $productId, $params = array())
    {
        return $this->sendRequest('get', $this->getUrl("/v1.2/accounts/$accountId/products/$productId/sales", $params));
    }

    /**
     * Retrieve app list of one analytics Account Connection
     *
     * @link http://support.appannie.com/hc/en-us/articles/204208964-2-Account-Connection-Product-List
     * @param type $accountId
     * @param type $params
     * @return type
     */
    public function getAccountApps($accountId, $params = array())
    {
        return $this->sendRequest('get', $this->getUrl("/v1.2/accounts/$accountId/products", $params));
    }

    /**
     * Retrieve an app’s detailed information.
     *
     * @link http://support.appannie.com/entries/23669989-2-App-Details
     * @param string $vertical apps | books
     * @param string $market ios | mac |google-play
     * @param type $appId Which app results should be retrieved. For iOS and MAC, it's application id that defined by apple, Google Play, it's application class name.
     * @return array
     *
     * Version 1.1 here as per recommendation from site.
     *
     */
    public function getAppDetails($vertical, $market, $appId)
    {
        return $this->sendRequest('get', $this->getUrl("/v1.1/$vertical/$market/app/$appId/details"));
    }

    /**
     * List of countries
     * @link http://support.appannie.com/hc/en-us/articles/204209024-1-Country-List-
     * @return array
     */
    public function getCountries()
    {
        return $this->sendRequest('get', $this->getUrl("/v1.2/meta/countries"));
    }

    /**
     * Category list by market
     *
     * @link http://support.appannie.com/entries/23744774-2-Category-List-
     * @param type $vertical apps | books
     * @param type $market ios | mac |google-play
     * @return type
     */
    public function getCategories($vertical, $market)
    {
        return $this->sendRequest('get', $this->getUrl("/v1.2/meta/$vertical/$market/categories"));
    }

    /**
     * List of platforms
     *
     * @link http://support.appannie.com/entries/21667604-3-Platform-List
     * @return array
     *
     * No replacement for this API in 1.2v
     *
     */
    public function getPlatforms()
    {
        return $this->sendRequest('get', $this->getUrl("/v1/meta/platforms"));
    }

    /**
     * Retrieve the In App Purchase list of one app
     * @link http://support.appannie.com/hc/en-us/articles/204208984-3-IAP-List
     * @param string $accountId
     * @param string $appId
     * @param array $params
     * @return array
     */
    public function getAccountAppIAPs($accountId, $productId, $params = array())
    {
        return $this->sendRequest('get', $this->getUrl("/v1.2/accounts/$accountId/products/$productId/iaps"));
    }

    /**
     * Build query string
     * @param array $params
     * @return string
     */
    protected function getParams(array $params)
    {
        $r = array();
        ksort($params);
        foreach ($params as $key => $value) {
            $r[] = $key . '=' . rawurlencode($value);
        }
        return implode('&', $r);
    }

    /**
     * Get url for service with method and params
     * @param string $method
     * @param array $params
     * @return string
     */
    protected function getUrl($method, $params = array())
    {
        $paramString = '';
        if (!empty($params)) {
            $paramString = '?'.$this->getParams($params);
        }

        return $this->apiUrl.$method.$paramString;
    }

    /**
     * Build request header array
     * @return array
     */
    protected function buildRequestHeader()
    {
        return array(
            'Authorization: bearer '.$this->apiKey,
            'Accept: application/json'
        );
    }

    /**
     * Send the request using curl
     * @param string $method
     * @param string $url
     * @param array $postParams
     * @return array
     */
    protected function sendRequest($method, $url, $postParams = array(), $verbose = false)
    {
        if ($verbose) {
            echo "$url\n";
        }
        $header = $this->buildRequestHeader();

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            //CURLOPT_VERBOSE => true
        );

        if ($method == 'post' && !empty($postParams)) {
            $options[CURLOPT_POST] = count($postParams);
            $options[CURLOPT_POSTFIELDS] = $this->getParams($postParams);
        }

        $c = curl_init();
        curl_setopt_array($c, $options);
        $response = curl_exec($c);
        curl_close($c);

        return $this->processResponse($response);
    }

    /**
     * Process response from web service
     *
     * @throws GrowlAppAnnieException
     * @param string $response
     * @return array
     */
    protected function processResponse($response)
    {
        $data = json_decode($response, true);
        if (!$data) {
            throw new GrowlAppAnnieException('Connection Error', 0);
        }
        if ($data['code'] !== 200) {
            throw new GrowlAppAnnieException($data['error'], $data['code']);
        }
        return $data;
    }
}

/**
 * Exception class
 */
class GrowlAppAnnieException extends Exception
{

}

