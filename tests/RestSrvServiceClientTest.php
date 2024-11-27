<?php

namespace RestAPI\Tests;

use RestAPI\Helper;
use RestAPI\RestSrvServiceClient;

/**
 * @internal
 * @coversNothing
 */
final class RestSrvServiceClientTest extends TestCase
{

    public function testGet()
    {
        $uri = '/aaa/bbb/ccc';
        RestSrvServiceClient::initialize(
            Helper::getEnv('restapi.sys_id'),
            Helper::getEnv('restapi.secret_key'),
            Helper::getEnv('restapi.region')
        );
        $r  = RestSrvServiceClient::get($uri);
        var_dump($r);
        $this->assertNotEmpty(true);
    }

    public function testRestPythonServiceBuildRequestUrl()
    {
        $uri = '/aaa/bbb/ccc';
        $r  = RestSrvServiceBuildRequestUrl($uri);
        var_dump($r);
        $this->assertNotEmpty(true);
    }


}
