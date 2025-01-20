<?php
namespace HoneySens\app\models\exceptions;

/**
 * Returned when attempting to access nonexistent entities.
 */
class NotFoundException extends \Exception {

    public function __construct($code = 0) {
        parent::__construct("", $code);
    }
}