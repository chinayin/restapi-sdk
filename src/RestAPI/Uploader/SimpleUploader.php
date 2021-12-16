<?php

namespace RestAPI\Uploader;

use RestAPI\RestServiceClient;

abstract class SimpleUploader
{
    protected $uploadUrl;
    protected $authToken;
    protected $userAgent;

    /**
     * Create uploader by provider.
     *
     * @param string $provider File provider: qiniu, s3, etc
     *
     * @return SimpleUploader
     */
    public static function createUploader(string $provider): SimpleUploader
    {
        if ('uhz' === $provider) {
            return new UhzUploader();
        }

        throw new \RuntimeException("File provider not supported: {$provider}");
    }

    /**
     * Encode file with params in multipart format.
     *
     * @param array  $file     File content, name, and mimeType
     * @param array  $params   Additional form params for provider
     * @param string $boundary Boundary string used for frontier
     *
     * @return string Multipart encoded string
     */
    public function multipartEncode(array $file, array $params, string $boundary): string
    {
        $body = "\r\n";

        foreach ($params as $key => $val) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$key}\"\r\n\r\n";
            $body .= "{$val}\r\n";
        }

        if (!empty($file)) {
            $mimeType = 'application/octet-stream';
            if (isset($file['mimeType'])) {
                $mimeType = $file['mimeType'];
            }
            $fieldname = static::getFileFieldName();
            // escape quotes in file name
            // 2020-11-13 FILTER_SANITIZE_MAGIC_QUOTES在7.3版本中被弃用
            if (version_compare(PHP_VERSION, '7.3', '<')) {
                $filename = filter_var($file['name'], FILTER_SANITIZE_MAGIC_QUOTES);
            } else {
                $filename = filter_var($file['name'], FILTER_SANITIZE_ADD_SLASHES);
            }
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$fieldname}\"; filename=\"{$filename}\"\r\n";
            $body .= "Content-Type: {$mimeType}\r\n\r\n";
            $body .= "{$file['content']}\r\n";
        }

        // append end frontier
        $body .= "--{$boundary}--\r\n";

        return $body;
    }

    /**
     * Initialize uploader with url and auth token.
     *
     * @param string $uploadUrl File provider url
     * @param string $authToken Auth token for file provider
     */
    public function initialize(string $uploadUrl, string $authToken)
    {
        $this->uploadUrl = $uploadUrl;
        $this->authToken = $authToken;
        $this->userAgent = RestServiceClient::getVersionString();
    }

    public function getUploadUrl()
    {
        return $this->uploadUrl;
    }

    public function getAuthToken()
    {
        return $this->authToken;
    }

    abstract public function upload($content, $mimeType, $key);

    abstract public function uploadWithLocalFile($filepath, $key);

    /**
     * The form field name of file content in multipart encoded data.
     *
     * @return string
     */
    protected static function getFileFieldName(): string
    {
        return 'file';
    }
}
