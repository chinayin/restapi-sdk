<?php

use RestAPI\Client;
use RestAPI\CloudException;
use RestAPI\Helper;
use RestAPI\RestAPIException;
use RestAPI\RestPayServiceClient;
use RestAPI\RestServiceClient;

/**
 * @throws RestAPIException
 */
function RestServicePost($path, $params, array $headers = []): array
{
    RestServiceClient::initialize(
        Helper::getEnv('restapi.sys_id'),
        Helper::getEnv('restapi.secret_key'),
        Helper::getEnv('restapi.region')
    );
    // timeout
    if (isset($headers['timeout']) && !empty($headers['timeout'])) {
        RestServiceClient::setTimeout($headers['timeout']);
        unset($headers['timeout']);
    }
    // server_url
    $serverUrl = Helper::getEnv('restapi.server_url');
    if (!empty($serverUrl)) {
        RestServiceClient::setServerUrl($serverUrl);
    }
    return RestServiceClient::post($path, $params, $headers);
}

/**
 * @throws RestAPIException
 */
function RestServiceGet($path, $params = null, array $headers = []): array
{
    RestServiceClient::initialize(
        Helper::getEnv('restapi.sys_id'),
        Helper::getEnv('restapi.secret_key'),
        Helper::getEnv('restapi.region')
    );
    // timeout
    if (isset($headers['timeout']) && !empty($headers['timeout'])) {
        RestServiceClient::setTimeout($headers['timeout']);
        unset($headers['timeout']);
    }
    // server_url
    $serverUrl = Helper::getEnv('restapi.server_url');
    if (!empty($serverUrl)) {
        RestServiceClient::setServerUrl($serverUrl);
    }
    return RestServiceClient::get($path, $params, $headers);
}

/**
 * @param $path
 *
 * @return string
 */
function RestServiceBuildRequestUrl($path): string
{
    RestServiceClient::initialize(
        Helper::getEnv('restapi.sys_id'),
        Helper::getEnv('restapi.secret_key'),
        Helper::getEnv('restapi.region')
    );
    // server_url
    $serverUrl = Helper::getEnv('restapi.server_url');
    if (!empty($serverUrl)) {
        RestServiceClient::setServerUrl($serverUrl);
    }
    return RestServiceClient::buildRequestUrl($path);
}

// ssoapi
function SsoClientInitialize($accessToken, $headers = [])
{
    Client::initialize(
        Helper::getEnv('restapi.sys_id'),
        Helper::getEnv('restapi.secret_key'),
        $accessToken
    );
    Client::useRegion(Helper::getEnv('restapi.region'));
    // timeout
    if (isset($headers['timeout']) && !empty($headers['timeout'])) {
        Client::setTimeout($headers['timeout']);
        unset($headers['timeout']);
    }
    // server_url
    $serverUrl = Helper::getEnv('restapi.server_url');
    if (!empty($serverUrl)) {
        Client::setServerUrl($serverUrl);
    }
}

/**
 * @throws CloudException
 */
function SsoClientGet($accessToken, $path, $params, $headers = []): array
{
    SsoClientInitialize($accessToken, $headers);
    return Client::get($path, $params, $headers);
}

/**
 * @throws CloudException
 */
function SsoClientPost($accessToken, $path, $params, $headers = []): array
{
    SsoClientInitialize($accessToken, $headers);
    return Client::post($path, $params, $headers);
}

/**
 * @throws CloudException
 */
function SsoClientPut($accessToken, $path, $params, $headers = []): array
{
    SsoClientInitialize($accessToken, $headers);
    return Client::put($path, $params, $headers);
}

/**
 * @throws CloudException
 */
function SsoClientDelete($accessToken, $path, $params, $headers = []): array
{
    SsoClientInitialize($accessToken, $headers);
    return Client::delete($path, $params, $headers);
}

// payapi
function PayClientInitialize($headers = [])
{
    // appid aprid
    if (isset($headers['app_id']) && !empty($headers['app_id'])) {
        RestPayServiceClient::setAppId($headers['app_id']);
        unset($headers['app_id']);
    }
    if (isset($headers['apr_id']) && !empty($headers['apr_id'])) {
        RestPayServiceClient::setAprId($headers['apr_id']);
        unset($headers['apr_id']);
    }
    if (isset($headers['app_secret']) && !empty($headers['app_secret'])) {
        RestPayServiceClient::setAppSecret($headers['app_secret']);
        unset($headers['app_secret']);
    }
    RestPayServiceClient::initialize(
        Helper::getEnv('restapi.sys_id'),
        Helper::getEnv('restapi.secret_key'),
        Helper::getEnv('restapi.region')
    );
    // timeout
    if (isset($headers['timeout']) && !empty($headers['timeout'])) {
        RestPayServiceClient::setTimeout($headers['timeout']);
        unset($headers['timeout']);
    }
    // server_url
    $serverUrl = Helper::getEnv('restapi.server_url');
    if (!empty($serverUrl)) {
        RestPayServiceClient::setServerUrl($serverUrl);
    }
}

/**
 * @throws RestAPIException
 */
function PayClientGet($path, $params = null, array $headers = []): array
{
    PayClientInitialize($headers);
    return RestPayServiceClient::get($path, $params, $headers);
}

/**
 * @throws RestAPIException
 */
function PayClientPost($path, $params = null, array $headers = []): array
{
    PayClientInitialize($headers);
    return RestPayServiceClient::post($path, $params, $headers);
}
