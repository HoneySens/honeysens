<?php
namespace HoneySens\app\models\exceptions;

class NotFoundException extends \Exception {

    public function __construct($code = 0) {
        parent::__construct("", $code);
    }
}