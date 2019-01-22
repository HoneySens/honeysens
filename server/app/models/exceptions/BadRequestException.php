<?php
namespace HoneySens\app\models\exceptions;

class BadRequestException extends \Exception {

    public function __construct($code = 0) {
        parent::__construct("", $code);
    }
}