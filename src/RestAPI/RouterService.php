<?php

namespace RestAPI;

class RouterService {
    const TTL_KEY = 'ttl';
    const API_SERVER_KEY = 'api_server';
    const IV_KEY = 'iv';
    private static $INSTANCES;
    private $sysId;
    private $region;

    private static $DEFAULT_REGION_ROUTE = [
        Region::DEV => 'srv.uhouzz.xyz',
        Region::TESTING => 'testapi.uhomes.com/srv',
        Region::PERVIEW => 'api.uhomes.com/srvpreview',
        Region::CN => 'api.uhomes.com/srv',
        Region::HK => 'hk-api.uhomes.com',
        Region::US => 'us-api.uhomes.com',
    ];

    private static $DEFAULT_REGION_IV = [
        Region::DEV => 'uhomescomtianlei',
        Region::TESTING => 'uhomescomtianlei',
        Region::CN => 'uhomescomtianlei',
        Region::HK => 'uhomescomtianlei',
        Region::US => 'uhomescomtianlei',
    ];

    private function __construct($sysId) {
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
    public static function getInstance($sysId) {
        if (isset(self::$INSTANCES[$sysId])) {
            return self::$INSTANCES[$sysId];
        }
        $router = new self($sysId);
        self::$INSTANCES[$sysId] = $router;

        return $router;
    }

    /**
     * Set region.
     *
     * See `RestAPI\Region` for available regions.
     *
     * @param mixed $region
     */
    public function setRegion($region) {
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
    public function getRoute($server_key) {
        $this->validate_server_key($server_key);
        $routes = $this->getDefaultRoutes();

        return isset($routes[$server_key]) ? $routes[$server_key] : null;
    }

    private function validate_server_key($server_key) {
        $routes = $this->getDefaultRoutes();
        if (!isset($routes[$server_key])) {
            throw new \InvalidArgumentException('Invalid server key.');
        }
    }

    /**
     * Fallback default routes, if app router not available.
     */
    private function getDefaultRoutes() {
        $host = self::$DEFAULT_REGION_ROUTE[$this->region];
        $iv = self::$DEFAULT_REGION_IV[$this->region];

        return [
            self::API_SERVER_KEY => $host,
            self::IV_KEY => $iv,
            self::TTL_KEY => 3600,
        ];
    }
}
