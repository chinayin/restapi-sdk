<?php

namespace Tests;

use RestAPI\Region;
use RestAPI\Router;

/**
 * @internal
 * @coversNothing
 */
final class RouterTest extends TestCase {
    public function testSetRegion() {
        $sysid = getenv('RESTAPI_SYS_ID');
        $router = Router::getInstance($this->genId(12));
        $router->setRegion('CN');
        $router->setRegion('HK');
        $router->setRegion(Region::CN);
        $router->setRegion(Region::HK);
    }

    public function testGetRoute() {
        $sysid = ('RESTAPI_SYS_ID');
        $router = Router::getInstance($sysid);
        $host = $router->getRoute(Router::API_SERVER_KEY);
        $this->assertSame(
            getenv('RESTAPI_API_SERVER'),
            $host
        );
    }

    private function getShortSysId($sysid) {
        return strtolower(substr($sysid, 0, 8));
    }

    private function genId($length) {
        return substr(str_shuffle(md5(rand())), 0, $length);
    }
}
