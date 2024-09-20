<?php
namespace HoneySens\app\models\platforms;
use Doctrine\ORM\Mapping\Entity;
use HoneySens\app\models\entities\Platform;

/**
 * Dockerized sensor platform
 */
#[Entity]
class DockerX86 extends Platform {}
