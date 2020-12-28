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

    public function testCurl()
    {
        $url = 'http://xxx/2.0/service/index/time';
        $method = 'POST';
        $data = [];
        $headers = [];
        $json = json_encode($data);

        $req = curl_init($url);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($req, CURLOPT_HTTPHEADER, array_map(
            function ($key, $val) { return "${key}: ${val}"; },
            array_keys($headers),
            $headers
        ));
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($req, CURLOPT_TIMEOUT, 5000);
        // curl_setopt($req, CURLINFO_HEADER_OUT, true);
        // 2020-12-23 返回 response_header, 如果不为 true, 只会获得响应的正文
        curl_setopt($req, CURLOPT_HEADER, true);
        curl_setopt($req, CURLOPT_ENCODING, '');
        switch ($method) {
            case 'GET':
                if ($data) {
                    // append GET data as query string
                    $url .= '?' . http_build_query($data);
                    curl_setopt($req, CURLOPT_URL, $url);
                }
                break;
            case 'POST':
                curl_setopt($req, CURLOPT_POST, 1);
                curl_setopt($req, CURLOPT_POSTFIELDS, $json);
                break;
            case 'PUT':
                curl_setopt($req, CURLOPT_POSTFIELDS, $json);
                curl_setopt($req, CURLOPT_CUSTOMREQUEST, $method);
                break;
            // no break
            case 'DELETE':
                curl_setopt($req, CURLOPT_CUSTOMREQUEST, $method);
                break;
            default:
                break;
        }
        $response = curl_exec($req);
        $curlInfo = curl_getinfo($req);
        $respCode = curl_getinfo($req, CURLINFO_HTTP_CODE);
        $respType = curl_getinfo($req, CURLINFO_CONTENT_TYPE);
        $error = curl_error($req);
        $errno = curl_errno($req);
        curl_close($req);

        var_dump("errno: $errno, error: $error");
        var_dump("respCode: $respCode, respType: $respType");
        echo str_pad('', 20, '-') . PHP_EOL;
        var_dump($curlInfo);
        echo str_pad('', 20, '-') . PHP_EOL;
        var_dump($response);
        echo str_pad('', 20, '-') . PHP_EOL;
        [$headers, $resp] = self::parseResponse($response);
        var_dump($headers);
        echo str_pad('', 20, '-') . PHP_EOL;
        var_dump($resp);
        echo str_pad('', 20, '-') . PHP_EOL;
        // 获取header中的request_id
        $request_id = isset($headers['x-request-id']) ? $headers['x-request-id'] : '';
        var_dump($request_id);
        $this->assertTrue(true);
    }

    private static function parseResponse($response)
    {
        if (($pos = strpos($response, "\r\n\r\n")) === false) {
            // Crap!
//            self::log($unionId, 'exception', "Missing header/body separator");
            throw new \Exception('Missing header/body separator', -1);
        }
        $headers = substr($response, 0, $pos);
        $body = substr($response, $pos + strlen("\n\r\n\r"));
        // Pretend CRLF = LF for compatibility (RFC 2616, section 19.3)
        $headers = str_replace("\r\n", "\n", $headers);
        // Unfold headers (replace [CRLF] 1*( SP | HT ) with SP) as per RFC 2616 (section 2.2)
        $headers = preg_replace('/\n[ \t]/', ' ', $headers);
        $headers = explode("\n", $headers);
        preg_match('#^HTTP/(1\.\d)[ \t]+(\d+)#i', array_shift($headers), $matches);
        if (empty($matches)) {
//            self::log($unionId, 'exception', "Response could not be parsed");
            throw new \Exception('Response could not be parsed', -1);
        }
        $header2 = [];
        foreach ($headers as $header) {
            [$key, $value] = explode(':', $header, 2);
            $value = trim($value);
            preg_replace('#(\s+)#i', ' ', $value);
            $header2[$key] = $value;
        }
        return [$header2, $body];
    }

}
