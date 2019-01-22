<?php
namespace HoneySens\app\controllers;
use FileUpload\PathResolver;
use FileUpload\FileSystem;
use FileUpload\FileUpload;
use FileUpload\File;
use HoneySens\app\models\entities\Firmware;
use HoneySens\app\models\exceptions\BadRequestException;
use Respect\Validation\Validator as V;

class Platforms extends RESTResource {

    const CREATE_ERROR_NONE = 0;
    const CREATE_ERROR_INVALID_IMAGE = 1;
    const CREATE_ERROR_INVALID_METADATA = 2;
    const CREATE_ERROR_DUPLICATE = 3;

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/api/platforms(/:id)/', function($id = null) use ($app, $em, $services, $config, $messages) {
            $controller = new Platforms($em, $services, $config);
            $criteria = array();
            $criteria['id'] = $id;
            $result = $controller->get($criteria);
            echo json_encode($result);
        });

        $app->get('/api/platforms/:id/firmware/current', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Platforms($em, $services, $config);
            $controller->downloadCurrentFirmwareForPlatform($id);
        });

        $app->get('/api/platforms/firmware/:id/raw', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Platforms($em, $services, $config);
            $controller->downloadFirmware($id);
        });

        $app->get('/api/platforms/firmware/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Platforms($em, $services, $config);
            echo json_encode($controller->getFirmware($id));
        });

        $app->post('/api/platforms/firmware', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Platforms($em, $services, $config);
            $imageData = $controller->create($_FILES['image']);
            echo json_encode($imageData);
        });

        $app->put('/api/platforms/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Platforms($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $platformData = json_decode($request);
            $image = $controller->update($id, $platformData);
            echo json_encode($image->getState());
        });

        $app->delete('/api/platforms/firmware/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Platforms($em, $services, $config);
            $controller->delete($id);
            echo json_encode([]);
        });
    }

    /**
     * Fetches platforms from the DB by various criteria:
     * - id: returns the platform with the given id
     * If no criteria are given, all platforms are returned.
     *
     * @param array $criteria
     * @return array
     */
    public function get($criteria) {
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('p')->from('HoneySens\app\models\entities\Platform', 'p');
        if(V::key('id', V::intVal())->validate($criteria)) {
            $qb->andWhere('p.id = :id')
                ->setParameter('id', $criteria['id']);
            return $qb->getQuery()->getSingleResult()->getState();
        } else {
            $platforms = array();
            foreach($qb->getQuery()->getResult() as $platform) {
                $platforms[] = $platform->getState();
            }
            return $platforms;
        }
    }

    /**
     * Fetches the firmware with the given id.
     *
     * @param $id
     * @return array
     */
    public function getFirmware($id) {
        $this->assureAllowed('get');
        $firmware = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Firmware')->find($id);
        V::objectType()->check($firmware);
        return $firmware->getState();
    }

    /**
     * Attempts to download the given firmware binary blob.
     * What exactly is offered to the client depends on the specific platform implementation.
     *
     * @param $id
     * @throws BadRequestException
     */
    public function downloadFirmware($id) {
        // Authenticate with either a valid sensor certificate or session
        $sensor = $this->checkSensorCert();
        if(!V::objectType()->validate($sensor)) {
            $this->assureAllowed('get');
        }
        $firmware = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Firmware')->find($id);
        V::objectType()->check($firmware);
        $platform = $firmware->getPlatform();
        $firmwarePath = $platform->obtainFirmware($firmware, $this->getServiceManager());
        // session_write_close(); Necessary?
        if($firmwarePath != null) $this->offerFile($firmwarePath, $firmware->getSource());
        else throw new BadRequestException();
    }

    /**
     * Attempts to identify and download the current default firmware for a given platform.
     *
     * @param $id
     */
    public function downloadCurrentFirmwareForPlatform($id) {
        $this->assureAllowed('get');
        $platform = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Platform')->find($id);
        V::objectType()->check($platform);
        $revision = $platform->getDefaultFirmwareRevision();
        V::objectType()->check($revision);
        $this->downloadFirmware($revision->getId());
    }

    /**
     * Creates and persists a new firmware revision.
     * It expects binary file data as parameter and supports chunked uploads.
     *
     * @param string $fileData
     * @return array
     */
    public function create($fileData) {
        $this->assureAllowed('create');
        $em = $this->getEntityManager();
        $pathresolver = new PathResolver\Simple(realpath(APPLICATION_PATH . '/../data/upload'));
        $fs = new FileSystem\Simple();
        $fileupload = new FileUpload($fileData, $_SERVER);
        $fileupload->setPathResolver($pathresolver);
        $fileupload->setFileSystem($fs);
        $fileupload->addCallback('completed', function(File $file) {
            // Validation
            global $em;
            // Check archive content
            exec('/bin/tar tzf ' . escapeshellarg($file->getRealPath()), $output);
            if(!in_array('firmware.img', $output) || !in_array('metadata.xml', $output))
                $this->handleInvalidUpload($file,Platforms::CREATE_ERROR_INVALID_IMAGE);
            // Check metadata
            $output = array();
            exec('/bin/tar xzf ' . escapeshellarg($file->getRealPath()) . ' metadata.xml -O', $output);
            try {
                $metadata = new \SimpleXMLElement(implode($output));
                V::objectType()
                    ->attribute('name')
                    ->attribute('version')
                    ->attribute('platform')
                    ->attribute('description')
                    ->check($metadata);
            } catch(\Exception $e) {
                $this->handleInvalidUpload($file, Platforms::CREATE_ERROR_INVALID_METADATA);
            }
            // Check platform existence
            $platform = $em->getRepository('HoneySens\app\models\entities\Platform')
                ->findOneBy(array('name' => (string) $metadata->platform));
            if(!V::objectType()->validate($platform))
                $this->handleInvalidUpload($file, Platforms::CREATE_ERROR_INVALID_METADATA);
            // Duplicate test
            $firmware = $em->getRepository('HoneySens\app\models\entities\Firmware')
                ->findOneBy(array('name' => (string) $metadata->name, 'version' => (string) $metadata->version));
            if(V::objectType()->validate($firmware))
                $this->handleInvalidUpload($file, Platforms::CREATE_ERROR_DUPLICATE);
            // Persistence
            $firmware = new Firmware();
            $firmware->setName((string) $metadata->name)
                ->setVersion((string) $metadata->version)
                ->setPlatform($platform)
                ->setDescription((string) $metadata->description)
                ->setChangelog('')
                ->setPlatform($platform);
            $platform->addFirmwareRevision($firmware);
            $platform->registerFirmware($firmware, $file, $this->getServiceManager());
            // Set this firmware as default if there isn't a default yet
            if(!$platform->hasDefaultFirmwareRevision()) {
                $platform->setDefaultFirmwareRevision($firmware);
            }
            $em->persist($firmware);
            $em->flush();
            $file->image = $firmware->getState();
        });
        list($files, $headers) = $fileupload->processAll();
        foreach($headers as $header => $value) {
            header($header . ': ' . $value);
        }
        return array('files' => $files);
    }

    /**
     * Updates platform metadata.
     * Only the default firmware revision can be changed.
     *
     * @param int $id
     * @param \stdClass $data
     * @return Firmware
     */
    public function update($id, $data) {
        $this->assureAllowed('update');
        // Validation
        V::intVal()->check($id);
        V::objectType()
            ->attribute('default_firmware_revision', V::intVal())
            ->check($data);
        // Persistence
        $em = $this->getEntityManager();
        $platform = $em->getRepository('HoneySens\app\models\entities\Platform')->find($id);
        V::objectType()->check($platform);
        $firmware = $em->getRepository('HoneySens\app\models\entities\Firmware')->find($data->default_firmware_revision);
        V::objectType()->check($firmware);
        $platform->setDefaultFirmwareRevision($firmware);
        $em->flush();
        return $platform;
    }

    /**
     * Removes a firmware revision from the given platform.
     *
     * @param $id
     * @throws BadRequestException
     */
    public function delete($id) {
        $this->assureAllowed('delete');
        // Validation
        V::intVal()->check($id);
        // Persistence
        $em = $this->getEntityManager();
        $firmware = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Firmware')->find($id);
        V::objectType()->check($firmware);
        $platform = $firmware->getPlatform();
        V::objectType()->check($platform);
        // Don't remove the default firmware revision for this platform
        if($platform->getDefaultFirmwareRevision() == $firmware) throw new BadRequestException();
        // In case this revision is set as target firmware on some platforms, reset those back to their default revision
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('s')->from('HoneySens\app\models\entities\Sensor', 's')
            ->where('s.firmware = :firmware')
            ->setParameter('firmware', $firmware);
        foreach($qb->getQuery()->getResult() as $sensor) {
            $sensor->setFirmware(null);
        }
        $platform->unregisterFirmware($firmware, $this->getServiceManager());
        $em->remove($firmware);
        $em->flush();
    }

    private function handleInvalidUpload(File $uploadedFile, $errorCode) {
        if(file_exists($uploadedFile->getRealPath())) exec('rm ' . escapeshellarg($uploadedFile->getRealPath()));
        throw new BadRequestException($errorCode);
    }
}