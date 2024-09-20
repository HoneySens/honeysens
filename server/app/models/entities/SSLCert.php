<?php
namespace HoneySens\app\models\entities;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: "certs")]
class SSLCert{

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    protected $id;

    #[Column(type: Types::TEXT)]
    protected $content;

    #[Column(type: Types::TEXT, nullable: true)]
    protected $privateKey;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set certificate content
     *
     * @param string $content
     * @return SSLCert
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    /**
     * Get certificate content
     *
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Set private key
     *
     * @param string $key
     * @return SSLCert
     */
    public function setKey($key) {
        $this->privateKey = $key;
        return $this;
    }

    /**
     * Get private key
     *
     * @return string
     */
    public function getKey() {
        return $this->privateKey;
    }

    /**
     * Returns the certificate fingerprint
     */
    public function getFingerprint() {
        return openssl_x509_fingerprint($this->getContent(), 'sha256');
    }

    public function getState() {
        return array(
            'id' => $this->getId(),
            'content' => $this->getContent(),
            'fingerprint' => $this->getFingerprint()
        );
    }
}
