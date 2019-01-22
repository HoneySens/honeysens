<?php
namespace HoneySens\app\models\exceptions;

class ForbiddenException extends \Exception {

    public function __construct($code = 0) {
        parent::__construct("", $code);
    }
}