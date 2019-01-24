<?php
namespace HoneySens\app\models;

class ContactService {
	
	private function getEventClassificationText($event) {
		if($event->getClassification() == $event::CLASSIFICATION_UNKNOWN) return 'Unbekannt';
		elseif($event->getClassification() == $event::CLASSIFICATION_ICMP) return 'ICMP-Paket';
		elseif($event->getClassification() == $event::CLASSIFICATION_CONN_ATTEMPT) return 'Verbindungsversuch';
		elseif($event->getClassification() == $event::CLASSIFICATION_LOW_HP) return 'Honeypot-Verbindung';
		elseif($event->getClassification() == $event::CLASSIFICATION_PORTSCAN) return 'Portscan';
	}
	
	private function prepareEMail($config) {
		$mail = new \PHPMailer();
		$mail->isSMTP();
		$mail->Host = $config['smtp']['server'];
		$mail->Port = $config['smtp']['port'];
		$mail->SMTPSecure = 'tls';
		$smtpUser = $config['smtp']['user'];
		if($smtpUser != '') {
			$mail->SMTPAuth = true;
			$mail->Username = $smtpUser;
			$mail->Password = $config['smtp']['password'];
		} else {
			$mail->SMTPAuth = false;
		}
		$mail->From = $config['smtp']['from'];
		$mail->FromName = 'HoneySens';
		$mail->WordWrap = 120;
		return $mail;		
	}
	
	public function sendIncident($config, $em, $event) {
        if($config['smtp']['enabled'] != 'true') return;
	    $qb = $em->createQueryBuilder();
	    $qb->select('c')->from('HoneySens\app\models\entities\IncidentContact', 'c')
            ->where('c.sendAllEvents = :all')
            ->setParameter('all', true);
        if($event->getClassification() >= $event::CLASSIFICATION_LOW_HP) {
            $qb->orWhere('c.sendCriticalEvents = :critical')
                ->setParameter('critical', true);
        }
        $contacts = $qb->getQuery()->getResult();
		if(count($contacts) == 0) return array('success' => true);
		$mail = $this->prepareEMail($config);
		foreach($contacts as $contact) {
			$mail->addAddress($contact->getEMail());
		}
		$classification = $this->getEventClassificationText($event);
		$mail->Subject = $event->getClassification() >= $event::CLASSIFICATION_LOW_HP ? "HoneySens: Kritischer Vorfall" : "HoneySens: Vorfall";
		$mail->Body = "Dies ist eine automatisch generierte Nachricht vom HoneySens-System, um auf einen Vorfall innerhalb ";
		$mail->Body .= "des Sensornetzwerkes hinzuweisen. Details entnehmen Sie der nachfolgenden Auflistung.\n\n####### Vorfall " . $event->getId() . " #######\n\n";
		$mail->Body .= "Datum: " . $event->getTimestamp()->format("d.m.Y") . "\n";
		$mail->Body .= "Zeit: " . $event->getTimestamp()->format("H:i:s") . "\n";
		$mail->Body .= "Sensor: " . $event->getSensor()->getName() . "\n";
		$mail->Body .= "Klassifikation: " . $classification . "\n";
		$mail->Body .= "Quelle: " . $event->getSource() . "\n";
		$mail->Body .= "Details: " . $event->getSummary() . "\n";
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
			$mail->Body .= "\n\n  Zusätzliche Informationen:\n  --------------------------\n";
			foreach($genericDetails as $genericDetail) {
				$mail->Body .= "  " . $genericDetail->getData() . "\n";
			}
		}
		if(count($interactionDetails) > 0) {
			$mail->Body .= "\n\n  Sensorinteraktion:\n  --------------------------\n";
			foreach($interactionDetails as $interactionDetail) {
				$mail->Body .= "  " . $interactionDetail->getTimestamp()->format('H:i:s') . ": " . $interactionDetail->getData() . "\n";
			}
		}
		if($mail->send()) {
			return array('success' => true);
		} else {
			return array('success' => false, 'error' => $mail->ErrorInfo);
		}
	}
	
	public function sendWeeklySummary($config, $em) {
        if($config['smtp']['enabled'] != 'true') return;
		$contacts = $em->getRepository('HoneySens\app\models\entities\IncidentContact')->findBy(array('sendWeeklySummary' => true));
		if(count($contacts) == 0) return array('success' => true);
		$mail = $this->prepareEMail($config);
		foreach($contacts as $contact) {
			$mail->addAddress($contact->getEMail());
		}
		$endInterval = new \Datetime();
		$startInterval = clone $endInterval;
		$startInterval = $startInterval->sub(new \DateInterval('P7D'));
		$sensorCount = count($em->getRepository('HoneySens\app\models\entities\Sensor')->findAll());
		$query = $em->createQuery('select e from HoneySens\app\models\entities\Event e where e.timestamp >= :timestamp')->setParameter('timestamp', $startInterval);
		$events = $query->getResult();
		$eventsBySensor = array();
		$criticalEvents = array();
		foreach($events as $event) {
			$sensor = $event->getSensor()->getName();
			if(array_key_exists($sensor, $eventsBySensor)) $eventsBySensor[$sensor] += 1;
			else $eventsBySensor[$sensor] = 1;
			if($event->getClassification() >= $event::CLASSIFICATION_LOW_HP) {
				$criticalEvents[] = $event;
			} 
		}
		$mail->Subject = 'HoneySens Zusammenfassung des Zeitraums vom ' . $startInterval->format('d.m.Y') . ' bis ' . $endInterval->format('d.m.Y');
		$mail->Body = "Dies ist eine automatisch generierte Nachricht vom HoneySens-System, um ueber den aktuellen Zustand des Sensornetzwerkes Auskunft zu geben ";
		$mail->Body .= "und die gesammelten Daten der letzten Woche zusammenzufassen.\n\nZeitraum: " . $startInterval->format('d.m.Y') . " - " . $endInterval->format('d.m.Y') . "\n\n";
		$mail->Body .= "Sensoren: " . $sensorCount . "\n";
		$mail->Body .= "Ereignisse: " . count($events) . ", davon " . count($criticalEvents) . " kritisch\n\n";
		if(count($events) > 0) {
			$mail->Body .= "### Ereignisse pro Sensor ###\n\n";
			foreach($eventsBySensor as $sensor => $amount) {
				$mail->Body .= "  " . $sensor . ": " . $amount . "\n";
			}
		}
		if(count($criticalEvents) > 0) {
			$mail->Body .= "\n### Kritische Ereignisse ###\n\n";
			foreach($criticalEvents as $event) {
				$classification = $this->getEventClassificationText($event);
				$mail->Body .= "  " . $event->getTimestamp()->format('d.m.Y H:i') . " (ID " . $event->getId() . "): " . $classification . " von " . $event->getSource() . " (" . $event->getSummary() . ")\n";
			}
		}
		if($mail->send()) {
			return array('success' => true);
		} else {
			return array('success' => false, 'error' => $mail->ErrorInfo);
		}		
	}

	public function sendTestMail($to, $server, $port, $user, $password, $from) {
		$mail = new \PHPMailer();
		$mail->isSMTP();
		$mail->Host = $server;
		$mail->Port = $port;
		$mail->SMTPSecure = 'tls';
		if($user != '') {
			$mail->SMTPAuth = true;
			$mail->Username = $user;
			$mail->Password = $password;
		} else {
			$mail->SMTPAuth = false;
		}
		$mail->From = $from;
		$mail->FromName = 'HoneySens';
		$mail->WordWrap = 120;
		$mail->addAddress($to);
		$mail->Subject = 'HoneySens Testnachricht"';
		$mail->Body = 'Dies ist eine Testnachricht des HoneySens-Servers.';
		if(!$mail->send()) throw new \Exception($mail->ErrorInfo);
	}
}