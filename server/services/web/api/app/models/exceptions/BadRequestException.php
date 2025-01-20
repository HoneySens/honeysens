<?php
namespace HoneySens\app\models\exceptions;

/**
 * Thrown on invalid or inconsistent service calls, e.g.
 * when attempting to update nonexistent entities.
 */
class BadRequestException extends \Exception {

    public function __construct($code = 0) {
        parent::__construct("", $code);
    }
}