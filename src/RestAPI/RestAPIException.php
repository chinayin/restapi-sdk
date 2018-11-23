<?php
namespace RestAPI;

/**
 * Exception thrown when cloud API returns error
 */
class RestAPIException extends \Exception {
    public function __construct($message, $code = 1) {
        parent::__construct($message, $code);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

