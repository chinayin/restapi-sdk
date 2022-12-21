<?php

namespace RestAPI;

class Router
{
    public const TTL_KEY = 'ttl';
    public const API_SERVER_KEY = 'api_server';
    public const IV_KEY = 'iv';
    public const IS_PRIVATE_ZONE_KEY = 'is_private_zone';
    public const CONST_IS_PRIVATE_ZONE_SERVER = 'DEPLOY_IS_VPC_ZONE';
    private static $INSTANCES;
    private $sysId;
    private $region;

    private static $DEFAULT_REGION_ROUTE = [
        Region::DEV => 'ssoapi.[domain].xyz',
        Region::TESTING => 'ssoapi-test.[domain].com',
        Region::UAT => 'ssoapi-uat.[domain].com',
        Region::CN => 'ssoapi.[domain].com',
        Region::HK => 'ssoapi-hk.[domain].com',
        Region::US => 'ssoapi-us.[domain].com',
        Region::UK => 'ssoapi-uk.[domain].com',
    ];

    private static $DEFAULT_LOCAL_REGION_ROUTE = [
        Region::CN => 'ssoapi.[domain].local',
        Region::TESTING => 'ssoapi.[domain]-test.local',
        Region::UAT => 'ssoapi.[domain]-uat.local',
        // 内网专线回国内
        Region::HK => 'ssoapi.[domain]-hk.local',
        Region::UK => 'ssoapi.[domain]-uk.local',
    ];

    private static $DEFAULT_REGION_IV = [
        Region::DEV => '[iv]',
        Region::TESTING => '[iv]',
        Region::UAT => '[iv]',
        Region::CN => '[iv]',
        Region::HK => '[iv]',
        Region::US => '[iv]',
        Region::UK => '[iv]',
    ];

    private function __construct($sysId)
    {
        $this->sysId = $sysId;
        $region = getenv('RESTAPI_REGION');
        if (!$region) {
            $region = Region::CN;
        }
        $this->setRegion($region);
    }

    /**
     * Get instance of Router.
     *
     * @param $sysId
     *
     * @return Router
     */
    public static function getInstance($sysId): Router
    {
        if (isset(self::$INSTANCES[$sysId])) {
            return self::$INSTANCES[$sysId];
        }
        $router = new self($sysId);
        self::$INSTANCES[$sysId] = $router;

        return $router;
    }

    /**
     * Set region.
     * See `RestAPI\Region` for available regions.
     *
     * @param mixed $region
     */
    public function setRegion($region)
    {
        if (is_numeric($region)) {
            $this->region = $region;
        } else {
            $this->region = Region::fromName($region);
        }
    }

    /**
     * Get and return route host by server type, or null if not found.
     *
     * @param $server_key
     *
     * @return null|mixed
     */
    public function getRoute($server_key)
    {
        $this->validate_server_key($server_key);
        $routes = $this->getDefaultRoutes();

        return $routes[$server_key] ?? null;
    }

    private function validate_server_key($server_key)
    {
        $routes = $this->getDefaultRoutes();
        if (!isset($routes[$server_key])) {
            throw new \InvalidArgumentException('Invalid server key.');
        }
    }

    /**
     * Fallback default routes, if app router not available.
     */
    private function getDefaultRoutes()
    {
        $host = self::$DEFAULT_REGION_ROUTE[$this->region];
        $iv = self::$DEFAULT_REGION_IV[$this->region];
        // 是否内网服务器
        $isPrivateZoneServer = getenv(self::CONST_IS_PRIVATE_ZONE_SERVER);
        $isPrivateZoneServer = $isPrivateZoneServer ? true : false;
        if ($isPrivateZoneServer && isset(self::$DEFAULT_LOCAL_REGION_ROUTE[$this->region])) {
            $host = self::$DEFAULT_LOCAL_REGION_ROUTE[$this->region];
        }
        // 替换变量
        $host = str_replace('[domain]', Domain::getMainDomain(), $host);
        $iv= str_replace('[iv]', Domain::getIVHash(), $iv);
        return [
            self::API_SERVER_KEY => $host,
            self::IV_KEY => $iv,
            self::TTL_KEY => 3600,
            self::IS_PRIVATE_ZONE_KEY => $isPrivateZoneServer,
        ];
    }

    /**
     * @return mixed
     */
    public function getSysId()
    {
        return $this->sysId;
    }
}

/**
 * Route cache.
 * Ideally we should use ACPu for caching, but it can be inconvenient to
 * install, esp. on Windows[1], thus we implement a naive file based
 * cache.
 * [1]: https://stackoverflow.com/a/28124144/108112
 */
class RouteCache
{
    private $filename;
    private $_cache;

    private function __construct($id)
    {
        $this->filename = sys_get_temp_dir() . "/restapi_route_{$id}.json";
    }

    public static function create($id)
    {
        return new self($id);
    }

    /**
     * Serialize array and store in file, array must be json_encode safe.
     *
     * @param mixed $array
     */
    public function write($array)
    {
        $body = json_encode($array);
        if (false === file_put_contents($this->filename, $body, LOCK_EX)) {
            error_log("WARNING: failed to write route cache ({$this->filename}), performance may be degraded.");
        } else {
            $this->_cache = $array;
        }
    }

    /**
     * Read routes either from cache or file, return json_decoded array.
     */
    public function read()
    {
        if ($this->_cache) {
            return $this->_cache;
        }
        $data = $this->readFile();
        if (!empty($data)) {
            $this->_cache = $data;

            return $data;
        }

        return null;
    }

    private function readFile()
    {
        if (file_exists($this->filename)) {
            $fp = fopen($this->filename, 'rb');
            $body = null;
            if (flock($fp, LOCK_SH)) {
                $body = fread($fp, filesize($this->filename));
                flock($fp, LOCK_UN);
            }
            fclose($fp);
            if (!empty($body)) {
                $data = @json_decode($body, true);
                if (!empty($data)) {
                    return $data;
                }
            }
        }

        return null;
    }
}
