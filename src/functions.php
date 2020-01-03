<?php

function RestServicePost($path, $params, $headers = [])
{
    try {
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
    } catch (\RestAPI\RestAPIException $e) {
        \RestAPI\Helper::log($e, 'RestAPIException');
        return false;
    }
}

function RestServiceGet($path, $params, $headers = [])
{
    try {
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
    } catch (\RestAPI\RestAPIException $e) {
        \RestAPI\Helper::log($e, 'RestAPIException');
        return false;
    }
}
