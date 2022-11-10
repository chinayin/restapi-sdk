<?php

namespace RestAPI;

use RestAPI\RouterPayService as Router;
use RestAPI\Storage\IStorage;
use RestAPI\Storage\SessionStorage;

/**
 * Client interfacing with REST API.
 * The client is responsible for sending request to API, and parsing
 * the response into objects in PHP. There are also utility functions
 * such as `::randomFloat` to generate a random float number.
 */
class RestPayServiceClient
{
    /**
     * Client version.
     */
    public const VERSION = '1.0.5';

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
     * logger flag
     *
     * @var bool
     */
    private static $logger;

    /** @var int */
    private static $appId;
    /** @var int */
    private static $aprId;
    /** @var string */
    private static $appSecret;

    /**
     * Initialize application key and settings.
     *
     * @param string $sysId Application ID
     * @param string $secretKey Application key
     * @param ?string $region Application region
     */
    public static function initialize(string $sysId, string $secretKey, string $region = null)
    {
        self::$sysId = $sysId;
        self::$secretKey = $secretKey;

        self::$defaultHeaders = [
            //'Content-Type' => 'application/json;charset=utf-8',
            'Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8',
            'Accept-Encoding' => 'gzip, deflate',
            'User-Agent' => self::getVersionString(),
            'X-Rest-Sysid' => self::$sysId,
            'X-Rest-Client' => self::VERSION,
            // 2022-01-12 客户端加密 新增
            'X-Client-Appid' => self::$appId,
            'X-Client-Aprid' => self::$aprId,
        ];

        // Use session storage by default
        if (null === self::$storage) {
            self::$storage = new SessionStorage();
        }

        self::useProduction('production' == getenv('RESTAPI_ENV'));
        if ($region) {
            self::useRegion($region);
        }
        // logger
        self::$logger = function_exists('__LOG_MESSAGE');
    }

    /**
     * Get version string used as user agent.
     *
     * @return string
     */
    public static function getVersionString(): string
    {
        return 'RESTAPI-SDK/' . self::VERSION;
    }

    /**
     * Set API region.
     * See `RestAPI\Region` for available regions.
     *
     * @param mixed $region
     */
    public static function useRegion($region)
    {
        self::assertInitialized();
        Router::getInstance(self::$sysId)->setRegion($region);
    }

    /**
     * Use production or not.
     *
     * @param bool $flag Default false
     */
    public static function useProduction(bool $flag)
    {
        self::$isProduction = $flag ? true : false;
    }

    /**
     * Set debug mode.
     * Enable debug mode to log request params and response.
     *
     * @param bool $flag Default false
     */
    public static function setDebug(bool $flag)
    {
        self::$debugMode = $flag ? true : false;
    }

    /**
     * Use master key or not.
     *
     * @param bool $flag
     */
    public static function useMasterKey(bool $flag)
    {
        self::$useMasterKey = $flag ? true : false;
    }

    /**
     * Set server url.
     * Explicitly set server url with which this client will communicate.
     * Url shall be in the form of: `https://api.xxxx.cn` .
     *
     * @param string $url
     */
    public static function setServerUrl(string $url)
    {
        self::$serverUrl = rtrim($url, '/');
    }

    /**
     * Set request client ip.
     *
     * @param string $clientIp
     */
    public static function setClientIp(string $clientIp)
    {
        self::$clientIp = $clientIp;
    }

    /**
     * Set user accesstoken.
     *
     * @param $accessToken
     */
    public static function setAccessToken($accessToken)
    {
        self::$accessToken = $accessToken;
    }

    /**
     * Set master key.
     *
     * @param $sysMasterKey
     */
    public static function setSysMasterKey($sysMasterKey)
    {
        self::$sysMasterKey = $sysMasterKey;
    }

    public static function getSysMasterKey()
    {
        return self::$sysMasterKey;
    }

    /**
     * Set timeout
     *
     * @param int $timeout Default 15
     */
    public static function setTimeout(int $timeout)
    {
        self::$apiTimeout = $timeout;
    }

    /**
     * Get API Endpoint.
     * The returned endpoint will include version string.
     * For example: https://api.xxxx.cn/1.1 .
     *
     * @return string
     */
    public static function getAPIEndPoint(): string
    {
        if ($url = self::$serverUrl) {
            return $url . '/' . self::$apiVersion;
        }
        if ($url = getenv('RESTAPI_API_SERVER')) {
            return $url . '/' . self::$apiVersion;
        }
        $host = Router::getInstance(self::$sysId)->getRoute(Router::API_SERVER_KEY);
        // 2020-01-14 如果是内网调用 使用http协议
        $isPrivateZone = Router::getInstance(self::$sysId)->getRoute(Router::IS_PRIVATE_ZONE_KEY);
        return ($isPrivateZone ? 'http' : 'https') . "://{$host}/" . self::$apiVersion;
    }

    /**
     * Build authentication headers.
     *
     * @param bool|null $useMasterKey
     *
     * @return array
     */
    public static function buildHeaders(?bool $useMasterKey): array
    {
        if (null === $useMasterKey) {
            $useMasterKey = self::$useMasterKey;
        }
        $h = self::$defaultHeaders;

        $h['X-Rest-Prod'] = self::$isProduction ? 1 : 0;
        if (self::$accessToken) {
            $h['X-Rest-Authorization'] = self::$accessToken;
        }
        $h['X-Client-Signature'] = self::buildHeaderAppSignature();

        return $h;
    }

    /**
     * Issue request to RestAPI.
     * The data is passing in as an associative array, which will be encoded
     * into JSON if the content-type header is "application/json", or
     * be appended to url as query string if it's GET request.
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
     * @param null $useMasterKey
     *                            Use master key or not
     *
     * @return mixed
     * @throws RestAPIException
     */
    public static function request(
        $method,
        $path,
        $data,
        array $headers = [],
        $useMasterKey = null
    ) {
        self::assertInitialized();
        $url = self::getAPIEndPoint();
        $unionId = rand(100, 999);
        // 强制/开头的path
        if (!str_starts_with($path, '/')) {
            throw new \RuntimeException(
                "${path} is not start with /",
                -1
            );
        }
        $path = '/' . ltrim($path, '/');
        $url .= $path;
        self::log($unionId, 'request', "$method $url");

        $defaultHeaders = self::buildHeaders($useMasterKey);
        if (empty($headers)) {
            $headers = $defaultHeaders;
        } else {
            $headers = array_merge($defaultHeaders, $headers);
        }
        $json = null;
        if (str_contains($headers['Content-Type'], '/json')) {
            $json = json_encode($data);
        } elseif (str_contains($headers['Content-Type'], '/x-www-form-urlencoded')) {
            $json = empty($data) ? '' : http_build_query($data);
        }

        // Build headers list in HTTP format
        $headersList = array_map(
            function ($key, $val) {
                return "${key}: ${val}";
            },
            array_keys($headers),
            $headers
        );

        $req = curl_init($url);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($req, CURLOPT_HTTPHEADER, $headersList);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($req, CURLOPT_TIMEOUT, self::$apiTimeout);
        // curl_setopt($req, CURLINFO_HEADER_OUT, true);
        // 2020-12-23 返回 response_header, 如果不为 true, 只会获得响应的正文
        curl_setopt($req, CURLOPT_HEADER, true);
        curl_setopt($req, CURLOPT_ENCODING, '');
        switch ($method) {
            case 'GET':
                if ($data) {
                    // append GET data as query string
                    $url .= '?' . http_build_query($data);
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
                break;
            case 'DELETE':
                curl_setopt($req, CURLOPT_CUSTOMREQUEST, $method);
                break;
            default:
                break;
        }
        if (self::$debugMode) {
            error_log("[DEBUG] HEADERS {$unionId}:" . json_encode($headersList));
            error_log("[DEBUG] REQUEST {$unionId}: {$method} {$url} {$json}");
        }
        $response = curl_exec($req);
        $curlInfo = curl_getinfo($req);
        $respCode = curl_getinfo($req, CURLINFO_HTTP_CODE);
        $respType = curl_getinfo($req, CURLINFO_CONTENT_TYPE);
        $error = curl_error($req);
        $errno = curl_errno($req);
        curl_close($req);
        // 2020-12-29 当curl_exec返回false
        if (false === $response) {
            self::log($unionId, 'exception', "-,CURL connection (${url}) curl_exec error: ${errno} ${error}");
            throw new \RuntimeException(
                "-,CURL connection (${url}) curl_exec error: ${errno} ${error}",
                $errno
            );
        }
        // 2020-12-23 带有header的response获取
        [$headers, $resp] = RequestHelper::parse_response($response, $unionId);
        // 获取header中的request_id
        $request_id = RequestHelper::parse_x_request_id($headers);
        if (self::$debugMode) {
            error_log("[DEBUG] RESPONSE {$unionId}: {$resp}");
        }
        /* type of error:
          *  - curl connection error
          *  - http status error 4xx, 5xx
          *  - rest api error
          */
        if ($errno > 0) {
            self::log($unionId, 'exception', "{$request_id},CURL connection (${url}) error: ${errno} ${error}");
            throw new \RuntimeException(
                "{$request_id},CURL connection (${url}) error: ${errno} ${error}",
                $errno
            );
        }
        // 非正常请求
        if (empty($respCode) || $respCode !== 200) {
            $respCodeText = HttpCode::getText($respCode);
            self::log($unionId, 'exception', "{$request_id},$respCode {$respCodeText} (${url})");
            throw new RestAPIException("{$request_id},$respCode {$respCodeText}", -1);
        }
        // 正常请求,单格式不对
        if (str_contains($respType, 'text/html')) {
            self::log($unionId, 'exception', "{$request_id},Bad request (${url})");
            throw new RestAPIException("{$request_id},Bad request", -1);
        }
        $data = json_decode($resp, true);
        empty($request_id) && $request_id = (empty($data) ? '-' : RequestHelper::parse_request_id($data));
        self::log(
            $unionId,
            'response',
            $request_id . ',' . json_encode(RequestHelper::parse_curlinfo($curlInfo), JSON_UNESCAPED_UNICODE)
        );

        if (isset($data['error_code']) && !empty($data['error_code'])) {
            $code = $data['error_code'] ?? -1;
            $e = new RestAPIException("{$data['message']}", $code);
            $e->setErrorData($data);
            throw $e;
        }
        return $data;
    }

    /**
     * @param $path
     *
     * @return string
     */
    public static function buildRequestUrl($path): string
    {
        return self::getAPIEndPoint() . '/' . ltrim($path, '/');
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
     * @param null $useMasterKey
     *                            Use master key or not, optional
     *
     * @return array
     * @throws RestAPIException
     * @see self::request()
     */
    public static function get(
        $path,
        $data = null,
        array $headers = [],
        $useMasterKey = null
    ): array {
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
     * @param null $useMasterKey
     *                            Use master key or not, optional
     *
     * @return array
     * @throws RestAPIException
     * @see self::request()
     */
    public static function post(
        $path,
        $data,
        array $headers = [],
        $useMasterKey = null
    ): array {
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
     * @param null $useMasterKey
     *                            Use master key or not, optional
     *
     * @return array
     * @throws RestAPIException
     * @see self::request()
     */
    public static function put(
        $path,
        $data,
        array $headers = [],
        $useMasterKey = null
    ): array {
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
     * @param null $useMasterKey
     *                            Use master key or not, optional
     *
     * @return array
     * @throws RestAPIException
     * @see self::request()
     */
    public static function delete(
        $path,
        array $headers = [],
        $useMasterKey = null
    ): array {
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
     * @param null $useMasterKey
     *                            Use master key or not, optional
     *
     * @return array
     * @throws RestAPIException
     * @see self::request()
     */
    public static function batch(
        array $requests,
        array $headers = [],
        $useMasterKey = null
    ): array {
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
     * @param \DateTime $date
     *
     * @return string
     */
    public static function formatDate(\DateTime $date): string
    {
        $utc = clone $date;
        $utc->setTimezone(new \DateTimezone('UTC'));
        $iso = $utc->format('Y-m-d\\TH:i:s.u');
        // Milliseconds precision is required for server to correctly parse time,
        // thus we have to chop off last 3 microseconds to milliseconds.
        return substr($iso, 0, 23) . 'Z';
    }

    /**
     * Get storage.
     *
     * @return IStorage
     */
    public static function getStorage(): IStorage
    {
        return self::$storage;
    }

    /**
     * Set storage.
     * It unset the storage if $storage is null.
     *
     * @param IStorage $storage
     */
    public static function setStorage(IStorage $storage)
    {
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
    public static function randomFloat(int $min = 0, int $max = 1)
    {
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
    public static function randomString($length = 10): string
    {
        return substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm'), 1, $length);
    }

    /**
     * Generate a app header Signature.
     *
     * @return string
     */
    private static function buildHeaderAppSignature(): string
    {
        $data = [
            'app_id' => self::$appId,
            'app_version' => defined('CLIENT_APP_VERSION') ? CLIENT_APP_VERSION : '',
            'api_version' => defined('CLIENT_API_VERSION') ? CLIENT_API_VERSION : '',
            '_time' => time(),
            '_salt' => self::randomString(10),
        ];
        $iv = Router::getInstance(self::$sysId)->getRoute(Router::IV_KEY);

        return base64_encode(
            openssl_encrypt(
                http_build_query($data),
                'AES-128-CBC',
                self::$appSecret,
                OPENSSL_RAW_DATA,
                $iv
            )
        );
    }

    /**
     * Assert client is correctly initialized.
     */
    private static function assertInitialized()
    {
        if (empty(self::$sysId) &&
            empty(self::$secretKey) &&
            empty(self::$sysMasterKey) &&
            empty(self::$appId) &&
            empty(self::$aprId) &&
            empty(self::$appSecret)
        ) {
            throw new \RuntimeException(
                'RestPayServiceClient is not initialized, ' .
                'please specify application key ' .
                'with RestPayServiceClient::initialize.'
            );
        }
    }

    /**
     * 日志记录函数.
     *
     * @param       $unionid
     * @param       $data
     * @param       $key
     */
    private static function log($unionid, $key, $data)
    {
        if (!self::$logger) {
            return;
        }
        __LOG_MESSAGE($data, "RestPayServiceClient($unionid)::$key");
    }

    public static function setAppId($appId)
    {
        self::$appId = $appId;
    }

    public static function setAprId($aprId)
    {
        self::$aprId = $aprId;
    }

    public static function setAppSecret($appSecret)
    {
        self::$appSecret = $appSecret;
    }
}
