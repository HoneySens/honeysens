<?php
namespace HoneySens\app\models;

use HoneySens\app\models\entities\Event;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\Template;

class ContactService {

    private $services;

    public function __construct($services) {
        $this->services = $services;
    }

    private function getEventClassificationText($event) {
        if($event->getClassification() == $event::CLASSIFICATION_UNKNOWN) return 'Unbekannt';
        elseif($event->getClassification() == $event::CLASSIFICATION_ICMP) return 'ICMP-Paket';
        elseif($event->getClassification() == $event::CLASSIFICATION_CONN_ATTEMPT) return 'Verbindungsversuch';
        elseif($event->getClassification() == $event::CLASSIFICATION_LOW_HP) return 'Honeypot-Verbindung';
        elseif($event->getClassification() == $event::CLASSIFICATION_PORTSCAN) return 'Portscan';
    }

    private function createEventSummary(Event $event) {
        $result = 'Datum: ' . $event->getTimestamp()->format('d.m.Y') . "\n";
        $result .= 'Zeit: ' . $event->getTimestamp()->format('H:i:s') . "\n";
        $result .= 'Sensor: ' . $event->getSensor()->getName() . "\n";
        $result .= 'Klassifikation: ' . $this->getEventClassificationText($event) . "\n";
        $result .= 'Quelle: ' . $event->getSource() . "\n";
        $result .= 'Details: ' . $event->getSummary();
        return $result;
    }

    private function createEventDetails(Event $event) {
        $result = '';
        $details = $event->getDetails();
        $genericDetails = array();
        $interactionDetails = array();
        if(count($details) > 0) {
            foreach($details as $detail) {
                if($detail->getType() == $detail::TYPE_GENERIC) $genericDetails[] = $detail;
                elseif($detail->getType() == $detail::TYPE_INTERACTION) $interactionDetails[] = $detail;
            }
        }
        if(count($genericDetails) > 0) {
            $itemCount = 0;
            $result .= "Zusätzliche Informationen:\n--------------------------\n";
            foreach($genericDetails as $genericDetail) {
                $itemCount++;
                $result .= $genericDetail->getData();
                if($itemCount != count($genericDetails)) $result .= "\n";
            }
        }
        if(count($interactionDetails) > 0) {
            $itemCount = 0;
            # Add an additional newline in case a generic details block exists for clear separation
            if(count($genericDetails) > 0) $result .= "\n";
            $result .= "Sensorinteraktion (Zeiten in UTC):\n----------------------------------\n";
            foreach($interactionDetails as $interactionDetail) {
                $itemCount++;
                $result .= $interactionDetail->getTimestamp()->format('H:i:s') . ': ' . $interactionDetail->getData();
                if($itemCount != count($interactionDetails)) $result .= "\n";
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
        $templateService = $this->services->get(ServiceManager::SERVICE_TEMPLATE);
        $body = $templateService->processTemplate(Template::TYPE_EMAIL_EVENT_NOTIFICATION, array(
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
            $taskService = $this->services->get(ServiceManager::SERVICE_TASK);
            $taskService->enqueue(null, Task::TYPE_EMAIL_EMITTER, $taskParams);
        }
        return array('success' => true);
    }

    /**
     * Enqueues and returns a task to send a test E-Mail with the given parameters.
     */
    public function sendTestMail($user, $from, $to, $smtpServer, $smtpPort, $smtpEncryption, $smtpUser, $smtpPassword) {
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
        $taskService = $this->services->get(ServiceManager::SERVICE_TASK);
        return $taskService->enqueue($user, Task::TYPE_EMAIL_EMITTER, $taskParams);
    }
}