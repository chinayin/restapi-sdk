<?php

namespace RestAPI;

use RestAPI\RouterService as Router;
use RestAPI\Storage\IStorage;
use RestAPI\Storage\SessionStorage;

/**
 * Client interfacing with REST API.
 *
 * The client is responsible for sending request to API, and parsing
 * the response into objects in PHP. There are also utility functions
 * such as `::randomFloat` to generate a random float number.
 */
class RestServiceClient {
    /**
     * Client version.
     */
    const VERSION = '0.2.0';

    /**
     * Is in production or not.
     *
     * @var bool
     */
    public static $isProduction = false;

    /**
     * API Version string.
     *
     * @var string
     */
    private static $apiVersion = '1.0';

    /**
     * API Timeout.
     *
     * Default to 15 seconds
     *
     * @var int
     */
    private static $apiTimeout = 15;

    /**
     * Application ID.
     *
     * @var string
     */
    private static $sysId;

    /**
     * Application Key.
     *
     * @var string
     */
    private static $secretKey;

    /**
     * Application master key.
     *
     * @var string
     */
    private static $sysMasterKey;

    /**
     *  Server url.
     *
     * @var string
     */
    private static $serverUrl;

    /**
     * Use master key or not.
     *
     * @var bool
     */
    private static $useMasterKey = false;

    /**
     * Is in debug mode or not.
     *
     * @var bool
     */
    private static $debugMode = false;

    /**
     * Default request headers.
     *
     * @var array
     */
    private static $defaultHeaders;

    /**
     * Persistent key-value storage.
     *
     * @var IStorage
     */
    private static $storage;

    /**
     * Request client ip.
     *
     * @var string
     */
    private static $clientIp;

    /**
     * User accesstoken.
     *
     * @var string
     */
    private static $accessToken;

    /**
     * Initialize application key and settings.
     *
     * @param string $sysId     Application ID
     * @param string $secretKey Application key
     * @param string $region    Application region
     */
    public static function initialize($sysId, $secretKey, $region = null) {
        self::$sysId = $sysId;
        self::$secretKey = $secretKey;

        self::$defaultHeaders = [
            'Content-Type' => 'application/json;charset=utf-8',
            'Accept-Encoding' => 'gzip, deflate',
            'User-Agent' => self::getVersionString(),
            'X-Rest-Sysid' => self::$sysId,
            'X-Rest-Client' => self::VERSION,
        ];

        // Use session storage by default
        if (!self::$storage) {
            self::$storage = new SessionStorage();
        }

        self::useProduction('production' == getenv('RESTAPI_ENV'));
        if ($region) {
            self::useRegion($region);
        }
    }

    /**
     * Get version string used as user agent.
     *
     * @return string
     */
    public static function getVersionString() {
        return 'RESTAPI-SDK/'.self::VERSION;
    }

    /**
     * Set API region.
     *
     * See `RestAPI\Region` for available regions.
     *
     * @param mixed $region
     */
    public static function useRegion($region) {
        self::assertInitialized();
        Router::getInstance(self::$sysId)->setRegion($region);
    }

    /**
     * Use production or not.
     *
     * @param bool $flag Default false
     */
    public static function useProduction($flag) {
        self::$isProduction = $flag ? true : false;
    }

    /**
     * Set debug mode.
     *
     * Enable debug mode to log request params and response.
     *
     * @param bool $flag Default false
     */
    public static function setDebug($flag) {
        self::$debugMode = $flag ? true : false;
    }

    /**
     * Use master key or not.
     *
     * @param bool $flag
     */
    public static function useMasterKey($flag) {
        self::$useMasterKey = $flag ? true : false;
    }

    /**
     * Set server url.
     *
     * Explicitly set server url with which this client will communicate.
     * Url shall be in the form of: `https://api.xxxx.cn` .
     *
     * @param string $url
     */
    public static function setServerUrl($url) {
        self::$serverUrl = rtrim($url, '/');
    }

    /**
     * Set request client ip.
     *
     * @param string $clientIp
     */
    public static function setClientIp($clientIp) {
        self::$clientIp = $clientIp;
    }

    /**
     * Set user accesstoken.
     *
     * @param $accessToken
     */
    public static function setAccessToken($accessToken) {
        self::$accessToken = $accessToken;
    }

    /**
     * Set master key.
     *
     * @param $sysMasterKey
     */
    public static function setSysMasterKey($sysMasterKey) {
        self::$sysMasterKey = $sysMasterKey;
    }

    /**
     * Get API Endpoint.
     *
     * The returned endpoint will include version string.
     * For example: https://api.xxxx.cn/1.1 .
     *
     * @return string
     */
    public static function getAPIEndPoint() {
        if ($url = self::$serverUrl) {
            return $url.'/'.self::$apiVersion;
        }
        if ($url = getenv('RESTAPI_API_SERVER')) {
            return $url.'/'.self::$apiVersion;
        }
        $host = Router::getInstance(self::$sysId)->getRoute(Router::API_SERVER_KEY);

        return "https://{$host}/".self::$apiVersion;
    }

    /**
     * Build authentication headers.
     *
     * @param bool $useMasterKey
     *
     * @return array
     */
    public static function buildHeaders($useMasterKey) {
        if (null === $useMasterKey) {
            $useMasterKey = self::$useMasterKey;
        }
        $h = self::$defaultHeaders;

        $h['X-Rest-Prod'] = self::$isProduction ? 1 : 0;

        $h['X-Rest-Signature'] = self::buildHeaderSignature();

        if (self::$accessToken) {
            $h['X-Rest-Authorization'] = self::$accessToken;
        }

        return $h;
    }

    /**
     * Issue request to RestAPI.
     *
     * The data is passing in as an associative array, which will be encoded
     * into JSON if the content-type header is "application/json", or
     * be appended to url as query string if it's GET request.
     *
     * The optional headers do have higher precedence, if provided it
     * will overwrite the items in default headers.
     *
     * @param       $method
     *                            GET, POST, PUT, DELETE
     * @param       $path
     *                            Request path (without version string)
     * @param       $data
     *                            Payload data
     * @param array $headers
     *                            Optional headers
     * @param null  $useMasterKey
     *                            Use master key or not
     *
     * @throws CloudException
     *
     * @return mixed
     */
    public static function request(
        $method,
        $path,
        $data,
        $headers = [],
        $useMasterKey = null
    ) {
        self::assertInitialized();
        $url = self::getAPIEndPoint();
        // 强制/开头的path
        if (0 !== strpos($path, '/')) {
            throw new \RuntimeException(
            "${path} is not start with /",
            -1
        );
        }
        $url .= '/'.ltrim($path, '/');

        $defaultHeaders = self::buildHeaders($useMasterKey);
        if (empty($headers)) {
            $headers = $defaultHeaders;
        } else {
            $headers = array_merge($defaultHeaders, $headers);
        }
        if (false !== strpos($headers['Content-Type'], '/json')) {
            $json = json_encode($data);
        }

        // Build headers list in HTTP format
        $headersList = array_map(
            function ($key, $val) { return "${key}: ${val}"; },
                                 array_keys($headers),
                                 $headers
        );

        $req = curl_init($url);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($req, CURLOPT_HTTPHEADER, $headersList);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($req, CURLOPT_TIMEOUT, self::$apiTimeout);
        // curl_setopt($req, CURLINFO_HEADER_OUT, true);
        // curl_setopt($req, CURLOPT_HEADER, true);
        curl_setopt($req, CURLOPT_ENCODING, '');
        switch ($method) {
            case 'GET':
                if ($data) {
                    // append GET data as query string
                    $url .= '?'.http_build_query($data);
                    curl_setopt($req, CURLOPT_URL, $url);
                }

                break;
            case 'POST':
                curl_setopt($req, CURLOPT_POST, 1);
                curl_setopt($req, CURLOPT_POSTFIELDS, $json);

                break;
            case 'PUT':
                curl_setopt($req, CURLOPT_POSTFIELDS, $json);
                curl_setopt($req, CURLOPT_CUSTOMREQUEST, $method);
                // no break
            case 'DELETE':
                curl_setopt($req, CURLOPT_CUSTOMREQUEST, $method);

                break;
            default:
                break;
        }
        $reqId = rand(100, 999);
        if (self::$debugMode) {
            error_log("[DEBUG] HEADERS {$reqId}:".json_encode($headersList));
            error_log("[DEBUG] REQUEST {$reqId}: {$method} {$url} {$json}");
        }
        // list($headers, $resp) = explode("\r\n\r\n", curl_exec($req), 2);
        $resp = curl_exec($req);
        $respCode = curl_getinfo($req, CURLINFO_HTTP_CODE);
        $respType = curl_getinfo($req, CURLINFO_CONTENT_TYPE);
        $error = curl_error($req);
        $errno = curl_errno($req);
        curl_close($req);

        if (self::$debugMode) {
            error_log("[DEBUG] RESPONSE {$reqId}: {$resp}");
        }

        /* type of error:
          *  - curl connection error
          *  - http status error 4xx, 5xx
          *  - rest api error
          */
        if ($errno > 0) {
            throw new \RuntimeException(
                "CURL connection (${url}) error: ".
                                        "${errno} ${error}",
                                        $errno
            );
        }
        if (false !== strpos($respType, 'text/html')) {
            throw new CloudException('Bad request', -1);
        }

        $data = json_decode($resp, true);
        if (isset($data['error_code']) && !empty($data['error_code'])) {
            $code = isset($data['error_code']) ? $data['error_code'] : -1;

            throw new CloudException("{$code} {$data['message']}", $code);
        }

        return $data;
    }

    /**
     * Issue GET request to RestAPI.
     *
     * @param       $path
     *                            Request path (without version string)
     * @param       $data
     *                            Payload data
     * @param array $headers
     *                            Optional headers
     * @param null  $useMasterKey
     *                            Use master key or not, optional
     *
     * @throws CloudException
     *
     * @return array
     *
     * @see self::request()
     */
    public static function get(
        $path,
        $data = null,
        $headers = [],
        $useMasterKey = null
    ) {
        return self::request(
            'GET',
            $path,
            $data,
            $headers,
            $useMasterKey
        );
    }

    /**
     * Issue POST request to RestAPI.
     *
     * @param       $path
     *                            Request path (without version string)
     * @param       $data
     *                            Payload data
     * @param array $headers
     *                            Optional headers
     * @param null  $useMasterKey
     *                            Use master key or not, optional
     *
     * @throws CloudException
     *
     * @return array
     *
     * @see self::request()
     */
    public static function post(
        $path,
        $data,
        $headers = [],
        $useMasterKey = null
    ) {
        return self::request(
            'POST',
            $path,
            $data,
            $headers,
            $useMasterKey
        );
    }

    /**
     * Issue PUT request to RestAPI.
     *
     * @param       $path
     *                            Request path (without version string)
     * @param       $data
     *                            Payload data
     * @param array $headers
     *                            Optional headers
     * @param null  $useMasterKey
     *                            Use master key or not, optional
     *
     * @throws CloudException
     *
     * @return array
     *
     * @see self::request()
     */
    public static function put(
        $path,
        $data,
        $headers = [],
        $useMasterKey = null
    ) {
        return self::request(
            'PUT',
            $path,
            $data,
            $headers,
            $useMasterKey
        );
    }

    /**
     * Issue DELETE request to RestAPI.
     *
     * @param       $path
     *                            Request path (without version string)
     * @param array $headers
     *                            Optional headers
     * @param null  $useMasterKey
     *                            Use master key or not, optional
     *
     * @throws CloudException
     *
     * @return array
     *
     * @see self::request()
     */
    public static function delete(
        $path,
        $headers = [],
        $useMasterKey = null
    ) {
        return self::request(
            'DELETE',
            $path,
            null,
            $headers,
            $useMasterKey
        );
    }

    /**
     * Issue batch request to RestAPI.
     *
     * @param array $requests
     *                            Array of requests in batch op
     * @param array $headers
     *                            Optional headers
     * @param null  $useMasterKey
     *                            Use master key or not, optional
     *
     * @throws CloudException
     *
     * @return array
     *
     * @see self::request()
     */
    public static function batch(
        $requests,
        $headers = [],
        $useMasterKey = null
    ) {
        $response = self::post(
            '/batch',
             ['requests' => $requests],
             $headers,
             $useMasterKey
        );

        $batchRequestError = new BatchRequestError();
        foreach ($requests as $i => $req) {
            if (isset($response[$i]['error_code']) && !empty(isset($response[$i]['error_code']))) {
                $batchRequestError->add($req, $response[$i]);
            }
        }

        if (!$batchRequestError->isEmpty()) {
            throw $batchRequestError;
        }

        return $response;
    }

    /**
     * Format date according to spec.
     *
     * @param DateTime $date
     *
     * @return string
     */
    public static function formatDate($date) {
        $utc = clone $date;
        $utc->setTimezone(new \DateTimezone('UTC'));
        $iso = $utc->format('Y-m-d\\TH:i:s.u');
        // Milliseconds precision is required for server to correctly parse time,
        // thus we have to chop off last 3 microseconds to milliseconds.
        return substr($iso, 0, 23).'Z';
    }

    /**
     * Get storage.
     *
     * @return IStorage
     */
    public static function getStorage() {
        return self::$storage;
    }

    /**
     * Set storage.
     *
     * It unset the storage if $storage is null.
     *
     * @param IStorage $storage
     */
    public static function setStorage($storage) {
        self::$storage = $storage;
    }

    /**
     * Generate a random float between [$min, $max).
     *
     * @param int $min
     * @param int $max
     *
     * @return float|int
     */
    public static function randomFloat($min = 0, $max = 1) {
        $M = mt_getrandmax();

        return $min + (mt_rand(0, $M - 1) / $M) * ($max - $min);
    }

    /**
     * Generate a random string.
     *
     * @param int length
     * @param mixed $length
     *
     * @return string
     */
    public static function randomString($length = 10) {
        return substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm'), 1, $length);
    }

    /**
     * Generate a header Signature.
     *
     * @return string
     */
    private static function buildHeaderSignature() {
        $data = [
            'sys_id' => self::$sysId,
            'version' => self::$apiVersion,
            'client_ip' => self::$clientIp,
            '_time' => time(),
            '_salt' => self::randomString(10),
        ];
        $iv = Router::getInstance(self::$sysId)->getRoute(Router::IV_KEY);

        return base64_encode(openssl_encrypt(
            http_build_query($data),
            'AES-256-CBC',
            self::$secretKey,
            OPENSSL_RAW_DATA,
            $iv
        ));
    }

    /**
     * Assert client is correctly initialized.
     */
    private static function assertInitialized() {
        if (!isset(self::$sysId) &&
            !isset(self::$secretKey) &&
            !isset(self::$sysMasterKey)) {
            throw new \RuntimeException('Client is not initialized, '.
                                        'please specify application key '.
                                        'with Client::initialize.');
        }
    }
}