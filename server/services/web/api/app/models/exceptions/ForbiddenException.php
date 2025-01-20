<?php
namespace HoneySens\app\models\exceptions;

/**
 * Thrown when the logged-in user is not permitted
 * to execute the current action.
 */
class ForbiddenException extends \Exception {

    public function __construct($code = 0) {
        parent::__construct("", $code);
    }
}