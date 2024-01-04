<?php
namespace HoneySens\app\models\entities;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="certs")
 */
class SSLCert{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(type="text")
     */
    protected $content;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
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