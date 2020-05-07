<?php

/**
 * @param       $path
 * @param       $params
 * @param array $headers
 *
 * @return array
 * @throws \RestAPI\RestAPIException
 */
function RestServicePost($path, $params, $headers = [])
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
function RestServiceGet($path, $params, $headers = [])
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
