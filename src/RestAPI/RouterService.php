<?php

namespace RestAPI;

class RouterService
{
    const TTL_KEY = 'ttl';
    const API_SERVER_KEY = 'api_server';
    const IV_KEY = 'iv';
    const IS_PRIVATE_ZONE_KEY = 'is_private_zone';
    const CONST_IS_PRIVATE_ZONE_SERVER = 'DEPLOY_IS_VPC_ZONE';
    private static $INSTANCES;
    private $sysId;
    private $region;

    private static $DEFAULT_REGION_ROUTE = [
        Region::DEV => 'srvapi.uhomes.xyz',
        Region::TESTING => 'srvapi-test.uhomes.com',
        Region::UAT => 'srvapi-uat.uhomes.com',
        Region::CN => 'srvapi.uhomes.com',
        Region::HK => 'srvapi-hk.uhomes.com',
        Region::US => 'srvapi-us.uhomes.com',
    ];

    private static $DEFAULT_LOCAL_REGION_ROUTE = [
        Region::CN => 'srvapi.uhomes.local',
        Region::TESTING => 'srvapi.uhomes-test.local',
        Region::UAT => 'srvapi-uat.uhomes-test.local',
    ];

    private static $DEFAULT_REGION_IV = [
        Region::DEV => 'uhomescomtianlei',
        Region::TESTING => 'uhomescomtianlei',
        Region::UAT => 'uhomescomtianlei',
        Region::CN => 'uhomescomtianlei',
        Region::HK => 'uhomescomtianlei',
        Region::US => 'uhomescomtianlei',
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
    public static function getInstance($sysId)
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

        return isset($routes[$server_key]) ? $routes[$server_key] : null;
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
        return [
            self::API_SERVER_KEY => $host,
            self::IV_KEY => $iv,
            self::TTL_KEY => 3600,
            self::IS_PRIVATE_ZONE_KEY => $isPrivateZoneServer,
        ];
    }
}
