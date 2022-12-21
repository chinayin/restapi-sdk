<?php

namespace RestAPI;

class RouterService
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
        Region::DEV => 'srvapi.[domain].xyz',
        Region::TESTING => 'srvapi-test.[domain].com',
        Region::UAT => 'srvapi-uat.[domain].com',
        Region::CN => 'srvapi.[domain].com',
        Region::HK => 'srvapi-hk.[domain].com',
        Region::US => 'srvapi-us.[domain].com',
        Region::UK => 'srvapi-uk.[domain].com',
    ];

    private static $DEFAULT_LOCAL_REGION_ROUTE = [
        Region::CN => 'srvapi.[domain].local',
        Region::TESTING => 'srvapi.[domain]-test.local',
        Region::UAT => 'srvapi.[domain]-uat.local',
        // 内网专线回国内
        Region::HK => 'srvapi.[domain]-hk.local',
        Region::UK => 'srvapi.[domain]-uk.local',
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
     * @return RouterService
     */
    public static function getInstance($sysId): RouterService
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
