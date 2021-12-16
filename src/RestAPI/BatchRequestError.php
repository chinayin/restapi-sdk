<?php

namespace RestAPI;

/**
 * BatchRequestError.
 *
 * A BatchRequestError object consists of zero or more request and
 * response errors.
 */
class BatchRequestError extends CloudException
{
    /**
     * Array of error response.
     *
     * @var array
     */
    private $errors = [];

    public function __construct($message = '', $code = 1)
    {
        $message = empty($message) ? 'Batch request error.' : $message;
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        $message = $this->message;
        if (!$this->isEmpty()) {
            $message .= json_encode($this->errors);
        }

        return __CLASS__.": [{$this->code}]: {$message}\n";
    }

    /**
     * Add failed request and its error response.
     *
     * Both request and response are expected to be array. The response
     * array must contain an `error` message, while the request should
     * contain `method` and `path`.
     *
     * @param mixed $request
     * @param mixed $response
     *
     * @return BatchRequestError
     */
    public function add($request, $response): BatchRequestError
    {
        $error['error_code'] = (isset($response['error_code']) && !empty($response['error_code']))
            ? $response['error_code'] : -1;
        $error['message'] = "{$error['error_code']} {$response['message']}:"
                        .json_encode($request);
        $this->errors[] = $error;

        return $this;
    }

    /**
     * Get all error response.
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->errors;
    }

    /**
     * Get first error response as map.
     *
     * Returns associative array of following format:
     *
     * `{"error_code": 101, "message": "error message", "request": {...}}`
     *
     * @return null|array
     */
    public function getFirst(): ?array
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Contains error response or not.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return 0 == count($this->errors);
    }
}
