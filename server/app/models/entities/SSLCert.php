<?php
namespace HoneySens\app\models\entities;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * Representation of a X.509 PEM-encoded certificate and
 * an optional key. Originally used to store sensor credentials
 * back when sensors authenticated via TLS client certificates.
 * Currently, certificates can be associated with sensors in the
 * context of EAPOL/802.1X authentication.
 */
#[Entity]
#[Table(name: "certs")]
class SSLCert{

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private $id;

    #[Column(type: Types::TEXT)]
    public string $content;

    #[Column(type: Types::TEXT, nullable: true)]
    public string $privateKey;

    public function getId(): int {
        return $this->id;
    }

    /**
     * Calculates and returns the certificate SHA256 fingerprint of this certificate.
     */
    public function getFingerprint(): string {
        return openssl_x509_fingerprint($this->content, 'sha256');
    }

    public function getState(): array {
        return array(
            'id' => $this->getId(),
            'content' => $this->content,
            'fingerprint' => $this->getFingerprint()
        );
    }
}
