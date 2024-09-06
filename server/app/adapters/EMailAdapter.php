<?php
namespace HoneySens\app\adapters;

use HoneySens\app\models\constants\EventDetailType;
use HoneySens\app\models\constants\TemplateType;
use HoneySens\app\models\entities\Event;
use HoneySens\app\models\entities\EventPacket;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\Template;

class EMailAdapter {

    private TaskAdapter $taskAdapter;
    private TemplateAdapter $templateAdapter;

    public function __construct(TaskAdapter $taskAdapter, TemplateAdapter $templateAdapter) {
        $this->taskAdapter = $taskAdapter;
        $this->templateAdapter = $templateAdapter;
    }

    private function getEventClassificationText($event) {
        if($event->getClassification() == $event::CLASSIFICATION_UNKNOWN) return 'Unbekannt';
        elseif($event->getClassification() == $event::CLASSIFICATION_ICMP) return 'ICMP-Paket';
        elseif($event->getClassification() == $event::CLASSIFICATION_CONN_ATTEMPT) return 'Verbindungsversuch';
        elseif($event->getClassification() == $event::CLASSIFICATION_LOW_HP) return 'Honeypot-Verbindung';
        elseif($event->getClassification() == $event::CLASSIFICATION_PORTSCAN) return 'Portscan';
    }

    private function getPacketProtocolAndPort($packet) {
        $protocol = 'UNK';
        switch($packet->getProtocol()) {
            case EventPacket::PROTOCOL_TCP: $protocol = 'TCP'; break;
            case EventPacket::PROTOCOL_UDP: $protocol = 'UDP'; break;
        }
        return $protocol . '/' . $packet->getPort();
    }

    private function getPacketFlags($packet) {
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

    private function getPayload($packet) {
        // Escape tabs and newlines
        $payload = $packet->getPayload();
        if($payload != null) {
            return str_replace("\t", "\\t", str_replace("\n", "\\n", base64_decode($packet->getPayload())));
        } else return '';
    }

    private function createEventSummary(Event $event) {
        $result = 'Datum: ' . $event->getTimestamp()->format('d.m.Y') . "\n";
        $result .= 'Zeit: ' . $event->getTimestamp()->format('H:i:s') . " (UTC)\n";
        $result .= 'Sensor: ' . $event->getSensor()->getName() . "\n";
        $result .= 'Klassifikation: ' . $this->getEventClassificationText($event) . "\n";
        $result .= 'Quelle: ' . $event->getSource() . "\n";
        $result .= 'Details: ' . $event->getSummary();
        return $result;
    }

    private function createEventDetails(Event $event) {
        $result = '';
        $details = $event->getDetails();
        $packets = $event->getPackets();
        $genericDetails = array();
        $interactionDetails = array();
        if(count($details) > 0) {
            foreach($details as $detail) {
                if($detail->getType() == EventDetailType::GENERIC) $genericDetails[] = $detail;
                elseif($detail->getType() == EventDetailType::INTERACTION) $interactionDetails[] = $detail;
            }
        }
        $detailBlockWritten = false;
        if(count($genericDetails) > 0) {
            $itemCount = 0;
            $result .= "Zusätzliche Informationen:\n--------------------------\n";
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
            $result .= "Sensorinteraktion (Zeiten in UTC):\n----------------------------------\n";
            foreach($interactionDetails as $interactionDetail) {
                $itemCount++;
                $result .= $interactionDetail->getTimestamp()->format('H:i:s') . ': ' . $interactionDetail->getData();
                if($itemCount != count($interactionDetails)) $result .= "\n";
            }
            $detailBlockWritten = true;
        }
        if(count($packets) > 0) {
            $itemCount = 0;
            # Add an additional newline in case a generic or interaction details block exists for visual separation
            if($detailBlockWritten) $result .= "\n";
            $result .= "Paketübersicht (Zeit in UTC | Protocol/Port | Flags | Payload):\n---------------------------------------------------------------\n";
            foreach($packets as $packet) {
                $itemCount++;
                $result .= $packet->getTimestamp()->format('H:i:s') . ': ' . $this->getPacketProtocolAndPort($packet) .
                    ' | ' . $this->getPacketFlags($packet) . ' | ' . $this->getPayload($packet);
                if($itemCount != count($packets)) $result .= "\n";
            }
        }
        return $result;
    }

    public function sendIncident($config, $em, $event) {
        if($config['smtp']['enabled'] != 'true') return;
        // Fetch associated contacts
        $division = $event->getSensor()->getDivision();
        $qb = $em->createQueryBuilder();
        $qb->select('c')->from('HoneySens\app\models\entities\IncidentContact', 'c')
            ->where('c.division = :division')
            ->setParameter('division', $division);
        if($event->getClassification() >= $event::CLASSIFICATION_LOW_HP) {
            $qb->andWhere('c.sendAllEvents = :all OR c.sendCriticalEvents = :critical')
                ->setParameter('all', true)
                ->setParameter('critical', true);
        } else {
            $qb->andWhere('c.sendAllEvents = :all')
                ->setParameter('all', true);
        }
        $contacts = $qb->getQuery()->getResult();
        if(count($contacts) == 0) return array('success' => true);
        // Prepare content
        $subject = $event->getClassification() >= $event::CLASSIFICATION_LOW_HP ? "HoneySens: Kritischer Vorfall" : "HoneySens: Vorfall";
        $body = $this->templateAdapter->processTemplate(TemplateType::EMAIL_EVENT_NOTIFICATION, array(
            'ID' => $event->getId(),
            'SUMMARY' => $this->createEventSummary($event),
            'DETAILS' => $this->createEventDetails($event)
        ));
        $taskParams = array(
            'subject' => $subject,
            'body' => $body);
        // Notify each contact
        foreach($contacts as $contact) {
            $taskParams['to'] = $contact->getEMail();
            $this->taskAdapter->enqueue(null, Task::TYPE_EMAIL_EMITTER, $taskParams);
        }
        return array('success' => true);
    }

    /**
     * Enqueues and returns a task to send a test E-Mail with the given parameters.
     */
    public function sendTestMail($user, $from, $to, $smtpServer, $smtpPort, $smtpEncryption, $smtpUser, $smtpPassword): Task {
        $taskParams = array(
            'test_mail' => true,
            'smtp_server' => $smtpServer,
            'smtp_port' => $smtpPort,
            'smtp_encryption' => $smtpEncryption,
            'from' => $from,
            'to' => $to,
            'subject' => 'HoneySens Testnachricht',
            'body' => 'Dies ist eine Testnachricht des HoneySens-Servers.');
        if($smtpUser != '') {
            $taskParams['smtp_user'] = $smtpUser;
            $taskParams['smtp_password'] = $smtpPassword;
        }
        return $this->taskAdapter->enqueue($user, Task::TYPE_EMAIL_EMITTER, $taskParams);
    }
}
