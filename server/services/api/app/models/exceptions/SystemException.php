<?php
namespace HoneySens\app\models\exceptions;

/**
 * A catch-all exception for unexpected system behaviour that may need
 * further investigation.
 */
class SystemException extends \Exception {

    public \Exception $nestedException;

    public function __construct(\Exception $e) {
        $this->nestedException = $e;
        parent::__construct($e->getMessage());
    }
}
