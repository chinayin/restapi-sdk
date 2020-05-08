<?php

namespace RestAPI\Tests;

use RestAPI\Client;

/**
 * @internal
 * @coversNothing
 */
final class ClientTest extends TestCase
{
    protected function setUp(): void
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
        $this->assertTrue(true);
    }

    public function testAPIEndPoint()
    {
        $url = getenv('RESTAPI_API_SERVER');
        $this->assertSame("{$url}/1.0", Client::getAPIEndPoint());

        Client::setServerURL('https://hello.xxx.net');
        $this->assertSame('https://hello.xxx.net/1.0', Client::getAPIEndPoint());
        Client::setServerURL(null);

        $this->assertSame("{$url}/1.0", Client::getAPIEndPoint());
    }

}
