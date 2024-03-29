<?php

namespace RestAPI\Uploader;

use RestAPI\MIMEType;

/**
 * Uhouzz file uploader.
 *
 * @see
 */
class UhzUploader extends SimpleUploader
{
    private $app_id;
    private $cust_id;
    private $user_id;
    private $type_id;
    private $post_params = [];

    public function crc32Data($data)
    {
        $hex = hash('crc32b', $data);
        $ints = unpack('N', pack('H*', $hex));

        return sprintf('%u', $ints[1]);
    }

    public function uploadWithLocalFile($filepath, $key)
    {
        $content = file_get_contents($filepath);
        if (false === $content) {
            throw new \RuntimeException("Read file error at ${filepath}");
        }
        $name = basename($filepath);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $mime = MIMEType::getType($ext);

        return $this->upload($content, $mime, $name);
    }

    /**
     * Upload file to uhouzz.
     *
     * @param $content  File content
     * @param $mimeType MIME type of file
     * @param $key      Generated file name
     *
     * @return mixed
     */
    public function upload($content, $mimeType, $key)
    {
        $boundary = md5((string)microtime(true));

        // 拼接参数
        $params = [
            'token' => $this->getAuthToken(),
            'key' => $key,
            'crc32' => $this->crc32Data($content),
        ];
        null === $this->app_id || $params['app_id'] = $this->app_id;
        null === $this->type_id || $params['file_type_id'] = $this->type_id;
        null === $this->cust_id || $params['cust_id'] = $this->cust_id;
        null === $this->user_id || $params['user_id'] = $this->user_id;
        $body = $this->multipartEncode([
            'name' => $key,
            'mimeType' => $mimeType,
            'content' => $content,
        ], array_merge($params, $this->post_params), $boundary);

        $headers[] = 'User-Agent: ' . $this->userAgent;
        $headers[] = 'Content-Type: multipart/form-data;' .
            " boundary={$boundary}";
        $headers[] = 'Content-Length: ' . strlen($body);

        $url = $this->getUploadUrl();
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $resp = curl_exec($ch);
        $respCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $respType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        /* type of error:
         *  - curl error
         *  - http status error 4xx, 5xx
         *  - rest api error
         */
        if ($errno > 0) {
            throw new \RuntimeException(
                "CURL (${url}) error: " .
                "{$errno} {$error}",
                $errno
            );
        }

        return json_decode($resp, true);
    }

    public function setUploadParams(array $params)
    {
        if (isset($params['app_id'])) {
            $this->setAppId((int)$params['app_id']);
            unset($params['app_id']);
        }
        if (isset($params['type_id'])) {
            $this->setTypeId((int)$params['type_id']);
            unset($params['type_id']);
        }
        if (isset($params['cust_id'])) {
            $this->setCustId((int)$params['cust_id']);
            unset($params['cust_id']);
        }
        if (isset($params['user_id'])) {
            $this->setUserId((int)$params['user_id']);
            unset($params['user_id']);
        }
        if (!empty($params)) {
            $this->post_params = $params;
        }
        return $this;
    }

    /**
     * @param int $app_id
     *
     * @return $this
     */
    public function setAppId(int $app_id)
    {
        $this->app_id = $app_id;

        return $this;
    }

    /**
     * @param int $cust_id
     *
     * @return $this
     */
    public function setCustId(int $cust_id)
    {
        $this->cust_id = $cust_id;

        return $this;
    }

    /**
     * @param int $user_id
     *
     * @return $this
     */
    public function setUserId(int $user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * @param int $type_id
     *
     * @return $this
     */
    public function setTypeId(int $type_id)
    {
        $this->type_id = $type_id;

        return $this;
    }
}
