<?php
namespace HoneySens\app\adapters;

use Doctrine\ORM\EntityManager;
use HoneySens\app\models\constants\EventClassification;
use HoneySens\app\models\constants\EventDetailType;
use HoneySens\app\models\constants\EventPacketProtocol;
use HoneySens\app\models\constants\TaskType;
use HoneySens\app\models\constants\TemplateType;
use HoneySens\app\models\constants\TransportEncryptionType;
use HoneySens\app\models\entities\Event;
use HoneySens\app\models\entities\EventPacket;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\SystemException;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;

/**
 * Prepares and sends out e-mails for incidents or test e-mails
 * via the external tasks queue.
 */
class EMailAdapter {

    private ConfigParser $config;
    private EntityManager $em;
    private TaskAdapter $taskAdapter;
    private TemplateAdapter $templateAdapter;

    public function __construct(ConfigParser $config, EntityManager $em, TaskAdapter $taskAdapter, TemplateAdapter $templateAdapter) {
        $this->config = $config;
        $this->em = $em;
        $this->taskAdapter = $taskAdapter;
        $this->templateAdapter = $templateAdapter;
    }

    /**
     * Enqueues and returns a task to send an e-mail notification
     * for a given Event to all contacts registered to be notified
     * for that specific event within a given division.
     *
     * @param Event $event The event to notify contacts about
     * @throws SystemException
     */
    public function sendIncident(Event $event): bool {
        if($this->config['smtp']['enabled'] !== 'true') return false;
        // Fetch associated contacts
        $division = $event->sensor->division;
        $qb = $this->em->createQueryBuilder();
        $qb->select('c')->from('HoneySens\app\models\entities\IncidentContact', 'c')
            ->where('c.division = :division')
            ->setParameter('division', $division);
        if($event->classification >= EventClassification::LOW_HP) {
            $qb->andWhere('c.sendAllEvents = :all OR c.sendCriticalEvents = :critical')
                ->setParameter('all', true)
                ->setParameter('critical', true);
        } else {
            $qb->andWhere('c.sendAllEvents = :all')
                ->setParameter('all', true);
        }
        $contacts = $qb->getQuery()->getResult();
        if(count($contacts) == 0) return true;
        // Prepare content
        $subject = $event->classification >= EventClassification::LOW_HP ? 'HoneySens: Critical event #' . $event->getId(): 'HoneySens: Event #' . $event->getId();
        $body = $this->templateAdapter->processTemplate(TemplateType::EMAIL_EVENT_NOTIFICATION, array(
            'ID' => $event->getId(),
            'SUMMARY' => $this->stringifyEventSummary($event),
            'DETAILS' => $this->stringifyEventDetails($event)
        ));
        $taskParams = array(
            'subject' => $subject,
            'body' => $body);
        // Notify each contact
        foreach($contacts as $contact) {
            $taskParams['to'] = $contact->getEMail();
            $this->taskAdapter->enqueue(null, TaskType::EMAIL_EMITTER, $taskParams);
        }
        return true;
    }

    /**
     * Enqueues and returns a task to send a test e-mail with the given parameters.
     *
     * @param User $user User to enqueue the task as
     * @param string $from Sender e-mail address
     * @param string $to Recipient e-mail address
     * @param string $smtpServer Host name or IP address of the SMTP server to connect to
     * @param int $smtpPort TCP port of the SMTP server to connect to
     * @param TransportEncryptionType $smtpEncryption TCP transport encryption mode
     * @param string $smtpUser User to authenticate as via SMTP
     * @param string $smtpPassword Password to authenticate with via SMTP
     * @throws SystemException
     */
    public function sendTestMail(User $user, string $from, string $to, string $smtpServer, int $smtpPort, TransportEncryptionType $smtpEncryption, string $smtpUser, string $smtpPassword): Task {
        $taskParams = array(
            'test_mail' => true,
            'smtp_server' => $smtpServer,
            'smtp_port' => $smtpPort,
            'smtp_encryption' => $smtpEncryption->value,
            'from' => $from,
            'to' => $to,
            'subject' => 'HoneySens test notification',
            'body' => 'This is a test notification from the HoneySens server.');
        if($smtpUser !== '') {
            $taskParams['smtp_user'] = $smtpUser;
            $taskParams['smtp_password'] = $smtpPassword;
        }
        return $this->taskAdapter->enqueue($user, TaskType::EMAIL_EMITTER, $taskParams);
    }

    private function stringifyEventClassificationText(Event $event): string {
        if($event->classification === EventClassification::ICMP) return 'ICMP';
        elseif($event->classification === EventClassification::CONN_ATTEMPT) return 'Connection attempt';
        elseif($event->classification === EventClassification::LOW_HP) return 'Honeypot connection';
        elseif($event->classification === EventClassification::PORTSCAN) return 'Scan';
        return 'Unknown';
    }

    private function stringifyPacketProtocolAndPort(EventPacket $packet): string {
        $protocol = 'UNK';
        switch($packet->protocol) {
            case EventPacketProtocol::TCP: $protocol = 'TCP'; break;
            case EventPacketProtocol::UDP: $protocol = 'UDP'; break;
            case EventPacketProtocol::UNKNOWN: $protocol = 'Unknown'; break;
        }
        return $protocol . '/' . $packet->port;
    }

    private function stringifyPacketFlags(EventPacket $packet): string {
        $headers = $packet->getHeaders();
        if(!$headers) return '';
        $flags = json_decode($headers, true)[0]['flags'];
        $result = '';
        if(($flags & 0b1) > 0) $result .= 'F';
        if(($flags & 0b10) > 0) $result .= 'S';
        if(($flags & 0b100) > 0) $result .= 'R';
        if(($flags & 0b1000) > 0) $result .= 'P';
        if(($flags & 0b10000) > 0) $result .= 'A';
        if(($flags & 0b100000) > 0) $result .= 'U';
        return $result;
    }

    private function stringifyPayload(EventPacket $packet): string {
        // Escape tabs and newlines
        $payload = $packet->getPayload();
        if($payload != null) {
            return str_replace("\t", "\\t", str_replace("\n", "\\n", base64_decode($packet->getPayload())));
        } else return '';
    }

    private function stringifyEventSummary(Event $event): string {
        $result = 'Date: ' . $event->timestamp->format('d.m.Y') . "\n";
        $result .= 'Time: ' . $event->timestamp->format('H:i:s') . " (UTC)\n";
        $result .= 'Sensor: ' . $event->sensor->name . "\n";
        $result .= 'Classification: ' . $this->stringifyEventClassificationText($event) . "\n";
        $result .= 'Source: ' . $event->source . "\n";
        $result .= 'Details: ' . $event->summary;
        return $result;
    }

    private function stringifyEventDetails(Event $event): string {
        $result = '';
        $details = $event->getDetails();
        $packets = $event->getPackets();
        $genericDetails = array();
        $interactionDetails = array();
        if(count($details) > 0) {
            foreach($details as $detail) {
                if($detail->type === EventDetailType::GENERIC) $genericDetails[] = $detail;
                elseif($detail->type === EventDetailType::INTERACTION) $interactionDetails[] = $detail;
            }
        }
        $detailBlockWritten = false;
        if(count($genericDetails) > 0) {
            $itemCount = 0;
            $result .= "Additional information:\n--------------------------\n";
            foreach($genericDetails as $genericDetail) {
                $itemCount++;
                $result .= $genericDetail->getData();
                if($itemCount != count($genericDetails)) $result .= "\n";
            }
            $detailBlockWritten = true;
        }
        if(count($interactionDetails) > 0) {
            $itemCount = 0;
            # Add an additional newline in case a generic details block exists for visual separation
            if($detailBlockWritten) $result .= "\n";
            $result .= "Sensor interaction (Times in UTC):\n----------------------------------\n";
            foreach($interactionDetails as $interactionDetail) {
                $itemCount++;
                $result .= $interactionDetail->timestamp->format('H:i:s') . ': ' . $interactionDetail->getData();
                if($itemCount != count($interactionDetails)) $result .= "\n";
            }
            $detailBlockWritten = true;
        }
        if(count($packets) > 0) {
            $itemCount = 0;
            # Add an additional newline in case a generic or interaction details block exists for visual separation
            if($detailBlockWritten) $result .= "\n";
            $result .= "Paket overview (Times in UTC | Protocol/Port | Flags | Payload):\n---------------------------------------------------------------\n";
            foreach($packets as $packet) {
                $itemCount++;
                $result .= $packet->timestamp->format('H:i:s') . ': ' . $this->stringifyPacketProtocolAndPort($packet) .
                    ' | ' . $this->stringifyPacketFlags($packet) . ' | ' . $this->stringifyPayload($packet);
                if($itemCount != count($packets)) $result .= "\n";
            }
        }
        return $result;
    }
}
