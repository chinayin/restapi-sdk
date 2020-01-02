<?php

namespace Tests;

use RestAPI\Client;

/**
 * @internal
 * @coversNothing
 */
final class ClientTest extends TestCase
{
    protected function setUp()
    {
        Client::initialize(
            getenv('RESTAPI_SYS_ID'),
            getenv('RESTAPI_SYS_KEY'),
            getenv('RESTAPI_SYS_MASTER_KEY')
        );
//        Client::useProduction(false);
        Client::useMasterKey(false);
        Client::setDebug(true);
    }

    public function testAPIRegion()
    {
        var_dump(Client::getAPIEndPoint());
        Client::useRegion('DEV');
        var_dump(Client::getAPIEndPoint());
        Client::useRegion('TESTING');
        var_dump(Client::getAPIEndPoint());
    }

    public function testAPIEndPoint()
    {
        $url = getenv('TEST_RESTAPI_API_SERVER');
        $this->assertSame("{$url}/1.0", Client::getAPIEndPoint());

        Client::setServerURL('https://hello.xxx.net');
        $this->assertSame('https://hello.xxx.net/1.0', Client::getAPIEndPoint());
        Client::setServerURL(null);

        $this->assertSame("{$url}/1.0", Client::getAPIEndPoint());
    }

    public function testPost()
    {
        $path = '/api/Oauth/checklogin';
        $data = [
            'email' => 'lei.tian@uhouzz.com',
            'password' => md5('Uhouzz@20170920'),
        ];
        $response = Client::post($path, $data);
        var_dump($response);
        $this->assertTrue(true);
    }

    public function testRequestServerDate()
    {
        $data = Client::request('GET', '/date', null);
        $this->assertSame($data['__type'], 'Date');
    }

    public function test_startWith()
    {
        foreach (['/aaa/bbb', 'ccc/ddd'] as $path) {
            if (0 !== strpos($path, '/')) {
                throw new \RuntimeException(
                    "${path} is not start with /",
                    -1
                );
            }
        }
    }
}
