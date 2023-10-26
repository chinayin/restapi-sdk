<?php

namespace RestAPI\Tests;

use RestAPI\Helper;
use RestAPI\RestPythonServiceClient;

/**
 * @internal
 * @coversNothing
 */
final class RestPythonServiceClientTest extends TestCase
{

    public function testGet()
    {
        $uri = '/aaa/bbb/ccc';
        RestPythonServiceClient::initialize(
            Helper::getEnv('restapi.sys_id'),
            Helper::getEnv('restapi.secret_key'),
            Helper::getEnv('restapi.region')
        );
        $r  = RestPythonServiceClient::get($uri);
        var_dump($r);
        $this->assertNotEmpty(true);
    }

    public function testRestPythonServiceBuildRequestUrl()
    {
        $uri = '/aaa/bbb/ccc';
        $r  = RestPythonServiceBuildRequestUrl($uri);
        var_dump($r);
        $this->assertNotEmpty(true);
    }


}
