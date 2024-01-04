<?php
namespace HoneySens\app\models\platforms;
use Doctrine\ORM\Mapping as ORM;
use HoneySens\app\models\entities\Platform;

/**
 * Dockerized sensor platform
 *
 * @ORM\Entity
 */
class DockerX86 extends Platform {}
