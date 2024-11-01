<?php

namespace FlexPayWoo;

class CancelSubscriptionException extends \Exception
{
    protected $status_code;

    protected $message;

    public function __construct($message, $status_code, \Exception $previous = null)
    {
        parent::__construct($message, $status_code, $previous);
        $this->message = $message;
        $this->status_code = $status_code;
    }

    public function get_status_code()
    {
        return $this->status_code;
    }

    public function get_message()
    {
        return $this->message;
    }
}