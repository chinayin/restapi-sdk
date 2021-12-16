<?php

namespace RestAPI;

/**
 * Exception thrown when cloud API returns error
 */
class RestAPIException extends \Exception
{
    protected $data;

    public function getErrorData()
    {
        return $this->data;
    }

    public function setErrorData($data)
    {
        $this->data = $data;
    }

    public function __construct($message, $code = 1)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
