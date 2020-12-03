<?php
namespace HoneySens\app\models;

use HoneySens\app\models\entities\Task;

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
        $classification = $this->getEventClassificationText($event);
        $subject = $event->getClassification() >= $event::CLASSIFICATION_LOW_HP ? "HoneySens: Kritischer Vorfall" : "HoneySens: Vorfall";
        $body = "Dies ist eine automatisch generierte Nachricht vom HoneySens-System, um auf einen Vorfall innerhalb ";
        $body .= "des Sensornetzwerkes hinzuweisen. Details entnehmen Sie der nachfolgenden Auflistung.\n\n####### Vorfall " . $event->getId() . " #######\n\n";
        $body .= "Datum: " . $event->getTimestamp()->format("d.m.Y") . "\n";
        $body .= "Zeit: " . $event->getTimestamp()->format("H:i:s") . "\n";
        $body .= "Sensor: " . $event->getSensor()->getName() . "\n";
        $body .= "Klassifikation: " . $classification . "\n";
        $body .= "Quelle: " . $event->getSource() . "\n";
        $body .= "Details: " . $event->getSummary() . "\n";
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
            $body .= "\n\n  Zusätzliche Informationen:\n  --------------------------\n";
            foreach($genericDetails as $genericDetail) {
                $body .= "  " . $genericDetail->getData() . "\n";
            }
        }
        if(count($interactionDetails) > 0) {
            $body .= "\n\n  Sensorinteraktion (Zeiten in UTC):\n  --------------------------\n";
            foreach($interactionDetails as $interactionDetail) {
                $body .= "  " . $interactionDetail->getTimestamp()->format('H:i:s') . ": " . $interactionDetail->getData() . "\n";
            }
        }
        // Notify each contact
        $taskParams = array(
            'subject' => $subject,
            'body' => $body);
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