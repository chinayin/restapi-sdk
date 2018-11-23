RestAPI PHP SDK
====

[![Latest Version](https://img.shields.io/packagist/v/chinayin/restapi-sdk.svg)
](https://packagist.org/packages/chinayin/restapi-sdk)

安装
----

运行环境要求 PHP 7.0 及以上版本，以及
[cURL](http://php.net/manual/zh/book.curl.php)。

#### composer 安装

如果使用标准的包管理器 composer，你可以很容易的在项目中添加依赖并下载：

```bash
composer require chinayin/restapi-sdk
```

初始化
----

完成上述安装后，需要对 SDK 初始化。
同时联系技术同学，获取SYS_ID 和 SECRET_KEY。
然后在项目中加载 SDK，并初始化：

#### REGION
* testing   测试环境
* cn        线上国内服务器
* hk        线上香港服务器
* us        线上美国服务器

```php
// 如果是 composer 安装
// require_once("vendor/autoload.php");

// 如果是手动安装
require_once("vendor/restapi-sdk/src/autoload.php");

//// SSO使用下面这个
// 参数依次为 sys-id, secret-key, access-token
RestAPI\Client::initialize("sys_id", "secret_key", "access_token");

//// SERVICE_API使用这个
// 参数依次为 sys-id, secret-key, region
RestAPI\RestServiceClient::initialize("sys_id", "secret_key", "region");
```

使用示例
----

#### 客户端请求 

##### SsoAPI 服务
```php
use RestAPI\Client;
use RestAPI\CloudException;

try {
    $response = Client::get('/api/oauth/get');
    $response = Client::post('/api/oauth/checklogin',[]);
    $response = Client::put('/api/oauth/put',[]);
    $response = Client::delete('/api/oauth/delete',[]);
} catch (CloudException $ex) {
    // 如果返回错误，这里会抛出异常 CloudException
    // 错误格式 错误码不为0都为报错
    // { "error_code": 1, "message": "error" }
}
```

##### ServiceAPI 服务
```php
use RestAPI\RestServiceClient;
use RestAPI\RestAPIException;

try {
    $response = RestServiceClient::get('/api/oauth/get');
    $response = RestServiceClient::post('/api/oauth/checklogin',[]);
    $response = RestServiceClient::put('/api/oauth/put',[]);
    $response = RestServiceClient::delete('/api/oauth/delete',[]);
} catch (RestAPIException $ex) {
    // 如果返回错误，这里会抛出异常 RestServiceClient
    // 错误格式 错误码不为0都为报错
    // { "error_code": 1, "message": "error" }
}
```

感谢
----
leancloud
