<?php

/**
 * @param       $path
 * @param       $params
 * @param array $headers
 *
 * @return array
 * @throws \RestAPI\RestAPIException
 */
function RestServicePost($path, $params, array $headers = []): array
{
    \RestAPI\RestServiceClient::initialize(
        \RestAPI\Helper::getEnv('restapi.sys_id'),
        \RestAPI\Helper::getEnv('restapi.secret_key'),
        \RestAPI\Helper::getEnv('restapi.region')
    );
    // timeout
    if (isset($headers['timeout']) && !empty($headers['timeout'])) {
        \RestAPI\RestServiceClient::setTimeout($headers['timeout']);
        unset($headers['timeout']);
    }
    // server_url
    if ($serverUrl = \RestAPI\Helper::getEnv('restapi.server_url')) {
        empty($serverUrl) || \RestAPI\RestServiceClient::setServerUrl($serverUrl);
    }
    return \RestAPI\RestServiceClient::post($path, $params, $headers);
}

/**
 * @param       $path
 * @param       $params
 * @param array $headers
 *
 * @return array
 * @throws \RestAPI\RestAPIException
 */
function RestServiceGet($path, $params = null, array $headers = []): array
{
    \RestAPI\RestServiceClient::initialize(
        \RestAPI\Helper::getEnv('restapi.sys_id'),
        \RestAPI\Helper::getEnv('restapi.secret_key'),
        \RestAPI\Helper::getEnv('restapi.region')
    );
    // timeout
    if (isset($headers['timeout']) && !empty($headers['timeout'])) {
        \RestAPI\RestServiceClient::setTimeout($headers['timeout']);
        unset($headers['timeout']);
    }
    // server_url
    if ($serverUrl = \RestAPI\Helper::getEnv('restapi.server_url')) {
        empty($serverUrl) || \RestAPI\RestServiceClient::setServerUrl($serverUrl);
    }
    return \RestAPI\RestServiceClient::get($path, $params, $headers);
}

/**
 * @param $path
 *
 * @return string
 */
function RestServiceBuildRequestUrl($path): string
{
    \RestAPI\RestServiceClient::initialize(
        \RestAPI\Helper::getEnv('restapi.sys_id'),
        \RestAPI\Helper::getEnv('restapi.secret_key'),
        \RestAPI\Helper::getEnv('restapi.region')
    );
    // server_url
    if ($serverUrl = \RestAPI\Helper::getEnv('restapi.server_url')) {
        empty($serverUrl) || \RestAPI\RestServiceClient::setServerUrl($serverUrl);
    }
    return \RestAPI\RestServiceClient::buildRequestUrl($path);
}

function SsoClientInitialize($accessToken, $headers = [])
{
    \RestAPI\Client::initialize(
        \RestAPI\Helper::getEnv('restapi.sys_id'),
        \RestAPI\Helper::getEnv('restapi.secret_key'),
        $accessToken
    );
    \RestAPI\Client::useRegion(\RestAPI\Helper::getEnv('restapi.region'));
    // timeout
    if (isset($headers['timeout']) && !empty($headers['timeout'])) {
        \RestAPI\Client::setTimeout($headers['timeout']);
        unset($headers['timeout']);
    }
    // server_url
    if ($serverUrl = \RestAPI\Helper::getEnv('restapi.server_url')) {
        empty($serverUrl) || \RestAPI\Client::setServerUrl($serverUrl);
    }
}

function SsoClientGet($accessToken, $path, $params, $headers = [])
{
    SsoClientInitialize($accessToken, $headers);
    return \RestAPI\Client::get($path, $params, $headers);
}

function SsoClientPost($accessToken, $path, $params, $headers = [])
{
    SsoClientInitialize($accessToken, $headers);
    return \RestAPI\Client::post($path, $params, $headers);
}

function SsoClientPut($accessToken, $path, $params, $headers = [])
{
    SsoClientInitialize($accessToken, $headers);
    return \RestAPI\Client::put($path, $params, $headers);
}

function SsoClientDelete($accessToken, $path, $params, $headers = [])
{
    SsoClientInitialize($accessToken, $headers);
    return \RestAPI\Client::delete($path, $params, $headers);
}
