<?php
namespace HoneySens\app\controllers;
use HoneySens\app\models\entities\Sensor;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\ForbiddenException;
use Slim\Slim;

abstract class RESTResource {
	
	protected $entityManager;
	protected $services;
	protected $config;
	protected $app;
	
	public function __construct($entityManager, $services, $config) {
		$this->entityManager = $entityManager;
		$this->services = $services;
		$this->config = $config;
		$this->app = Slim::getInstance();
	}
	
	protected function getEntityManager() {
		return $this->entityManager;
	}
	
	protected function getApp() {
		return $this->app;
	}
	
	protected function getServiceManager() {
		return $this->services;
	}
	
	protected function getConfig() {
		return $this->config;
	}

    abstract static function registerRoutes($app, $em, $services, $config, $messages);

	protected function assureAllowed($method, $realm=null) {
		if($realm) {
            if(!in_array($method, $_SESSION['user']['permissions'][$realm])) throw new \Exception('Not permitted.');
        } else {
            if(!in_array($method, $_SESSION['user']['permissions'][strtolower(str_replace('HoneySens\\app\\controllers\\', '', get_class($this)))])) {
                throw new ForbiddenException();
            }
        }
	}
	
	protected function offerFile($path, $name) {
		if(!file_exists($path)) {
			header('HTTP/1.0 400 Bad Request');
			exit;
		}
		@apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_comproession', 'Off');
		set_time_limit(0);
		ob_end_clean();
		ob_end_clean();
		if(file_exists($path)) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . $name);
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($path));
			readfile($path);
			exit;
		}
	}

	/**
	 * Computes an ArrayCollection update with a list of entity IDs. Returns an array of the form
	 * array('add' => array(), 'update' => array(), 'remove' => array())
	 * that specifies the required tasks to complete the operation.
	 *
	 * @param $collection ArrayCollection of the entities to be updated
	 * @param $ids Array of entity IDs to update the collection with
	 * @param $repository Repository to fetch entities from
	 * @return array Specifies tasks to perform to perform the operation
	 */
	protected function updateCollection($collection, &$ids, $repository) {
		$tasks = array('add' => array(), 'update' => array(), 'remove' => array());
		foreach($collection as $entity) {
			if(in_array($entity->getId(), $ids)) {
				$tasks['update'][] = $entity;
				if(($key = array_search($entity->getId(), $ids)) !== false) {
					unset($ids[$key]);
					$ids = array_values($ids);
				}
			} else {
				$tasks['remove'][] = $entity;
			}
		}
		foreach($ids as $entityId) {
			$entity = $repository->find($entityId);
			if($entity) $tasks['add'][] = $entity;
		}
		return $tasks;
	}

    /**
     * Returns the user id of the currently loggend in user or null in case of an admin user.
     * This means that for both admin and guest users null is returned, which means that an additional permission check is
     * required. This step is usually done inside of the resource classes/controllers.
     *
     * @return null|integer
     */
    public function getSessionUserID() {
        if($_SESSION['user']['role'] == User::ROLE_ADMIN) {
            return null;
        } else return $_SESSION['user']['id'];
    }

    /**
     * Determines if
     * 1) a valid client certificate was provided
     * 2) that belongs to a currently registered sensor (CN equals sensor ID)
     * and return that sensor instance, otherwise return null.
     *
     * @return null|Sensor
     */
    protected function checkSensorCert() {
        if(isset($_SERVER['SSL_CLIENT_VERIFY']) && $_SERVER['SSL_CLIENT_VERIFY'] === 'SUCCESS') {
            $cn = $_SERVER['SSL_CLIENT_S_DN_CN'];
            $sensorID = substr($cn, 1); // Remove first character (CN are currently 's' + sensor id)
            $sensor = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Sensor')->find($sensorID);
            return $sensor;
        } else return null;
    }
}