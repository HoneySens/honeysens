<?php
namespace HoneySens\app\models;

use \Doctrine\ORM\Events;
use \Doctrine\Common\EventSubscriber;
use \Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * This class refreshes the timestamps in the 'last_updates' table
 * on every INSERT/DELETE/UPDATE. Those values are used to supply clients
 * with all recent updates to avoid full refetches every few seconds.
 */
class EntityUpdateSubscriber implements EventSubscriber {
	
	public function getSubscribedEvents() {
		return array(
			Events::postPersist,
			Events::postRemove,
			Events::postUpdate
		);
	}
	
	public function postPersist(LifecycleEventArgs $args) {
		$this->updateTimestamp($args->getEntityManager(), $args->getEntity());
	}
	
	public function postRemove(LifecycleEventArgs $args) {
		$this->updateTimestamp($args->getEntityManager(), $args->getEntity());
	}
	
	public function postUpdate(LifecycleEventArgs $args) {
		$this->updateTimestamp($args->getEntityManager(), $args->getEntity());
	}
	
	public function updateTimestamp($em, $entity) {
		$table = null;
		if($entity instanceof entities\Firmware) $table = 'platforms';
		elseif($entity instanceof entities\Sensor || $entity instanceof entities\SensorStatus) $table = 'sensors';
		elseif($entity instanceof entities\User) $table = 'users';
		elseif($entity instanceof entities\Division) $table = 'divisions';
		elseif($entity instanceof entities\IncidentContact) $table = 'contacts';
		elseif($entity instanceof entities\EventFilter) $table = 'event_filters';
        elseif($entity instanceof entities\Event) $table = 'stats';
        elseif($entity instanceof entities\Service || $entity instanceof entities\ServiceRevision) $table = 'services';
		if($table) {
			$em->getConnection()->executeUpdate('UPDATE last_updates SET timestamp = NOW() WHERE table_name = ?', array($table));
		}
	}
}