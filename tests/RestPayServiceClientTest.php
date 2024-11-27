<?php

namespace RestAPI\Tests;

/**
 * @internal
 * @coversNothing
 */
final class RestPayServiceClientTest extends TestCase
{

    public function testGet()
    {
        $uri = '/payapi/Pay/payment_type?payment_category_id=1&currency_id=1';
        $r = PayClientGet($uri);
        var_dump($r);
        $this->assertTrue(true);
    }

}
