<?php
namespace HoneySens\app\controllers;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use FileUpload\FileSystem\Simple;
use FileUpload\FileUpload;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\ServiceManager;
use Respect\Validation\Validator as V;

class Tasks extends RESTResource {

    const UPLOAD_PATH = 'upload';

    const UPLOAD_TYPE_SERVICE_ARCHIVE = 0;
    const UPLOAD_TYPE_PLATFORM_ARCHIVE = 1;

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/api/tasks(/:id)/', function($id = null) use ($app, $em, $services, $config, $messages) {
            $controller = new Tasks($em, $services, $config);
            $criteria = array('userID' => $controller->getSessionUserID(), 'id' => $id);
            try {
                $result = $controller->get($criteria);
            } catch(Exception $e) {
                throw new NotFoundException();
            }
            echo json_encode($result);
        });

        $app->get('/api/tasks/:id/result(/:delete)', function($id, $delete = null) use ($app, $em, $services, $config, $messages) {
            $controller = new Tasks($em, $services, $config);
            $controller->downloadResult($id, boolval($delete));
        });

        $app->get('/api/tasks/status', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Tasks($em, $services, $config);
            if($controller->getWorkerStatus()) echo json_encode([]);
            else throw new NotFoundException();
        });

        // Generic endpoint to upload files, returns the ID of the associated verification task.
        $app->post('/api/tasks/upload', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Tasks($em, $services, $config);
            echo json_encode($controller->upload($_FILES['upload']));
        });

        $app->delete('/api/tasks/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Tasks($em, $services, $config);
            $controller->delete($id);
            echo json_encode([]);
        });
    }

    /**
     * Fetches tasks from the DB by various criteria:
     * - userID: return only tasks that belong to the user with the given id
     * - id: return the task with the given id
     * If no criteria are given, all tasks are returned.
     *
     * @param $criteria
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ForbiddenException
     */
    public function get($criteria) {
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('t')->from('HoneySens\app\models\entities\Task', 't');
        if(V::key('userID', V::intType())->validate($criteria)) {
            $qb->join('t.user', 'u')
                ->andWhere('u.id = :user')
                ->setParameter('user', $criteria['userID']);
        }
        if(V::key('id', V::intVal())->validate($criteria)) {
            $qb->andWhere('t.id = :id')
                ->setParameter('id', $criteria['id']);
            return $qb->getQuery()->getSingleResult()->getState();
        } else {
            $tasks = array();
            foreach($qb->getQuery()->getResult() as $task) {
                $tasks[] = $task->getState();
            }
            return $tasks;
        }
    }

    /**
     * Attempts to download the result of the given task.
     * If there is no downloadable result, this will return an exception.
     * Set $delete to true to delete the resource that was downloaded afterwards.
     *
     * @param $id
     * @param bool $delete
     * @throws BadRequestException
     * @throws ForbiddenException
     */
    public function downloadResult($id, $delete=false) {
        // TODO Ensure that a user can only download his own task's results
        $this->assureAllowed('get');
        $task = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Task')->find($id);
        V::objectType()->check($task);
        $result = $task->getResult();
        $controller = $this;
        $deleteFunc = function() use ($controller, $id) {
            $controller->delete($id);
        };
        if($task->getStatus() == Task::STATUS_DONE && array_key_exists('path', $result)) {
            $filepath = sprintf("%s/tasks/%s/%s", $this->getConfig()['server']['data_path'], $id, $result['path']);
            if($delete) $this->offerFile($filepath, $result['path'], $deleteFunc);
            else $this->offerFile($filepath, $result['path']);
        } else throw new BadRequestException();
    }

    /**
     * Queries the task worker for availability.
     *
     * @return bool
     * @throws ForbiddenException
     */
    public function getWorkerStatus() {
        $this->assureAllowed('get');
        return $this->getServiceManager()->get(ServiceManager::SERVICE_TASK)->isAvailable();
    }

    /**
     * Application-wide upload endpoint.
     * Supports chunked uploads and launches a new verification task for each uploaded file.
     *
     * @param $data
     * @return array
     */
    public function upload($data) {
        // Only users that are logged in can upload stuff
        V::objectType()->check($this->getSessionUser());
        $pathResolver = new \FileUpload\PathResolver\Simple(realpath(sprintf('%s/%s', $this->getConfig()['server']['data_path'], self::UPLOAD_PATH)));
        $fs = new Simple();
        $fileUpload = new FileUpload($data, $_SERVER);
        $fileUpload->setPathResolver($pathResolver);
        $fileUpload->setFileSystem($fs);
        list($files, $headers) = $fileUpload->processAll();
        $result = array('files' => $files);
        foreach($files as $file) {
            if($file->completed) {
                // Verify archive content
                $taskParams = array('path' => $file->getBasename());
                $task = $this->getServiceManager()->get(ServiceManager::SERVICE_TASK)->enqueue($this->getSessionUser(), Task::TYPE_UPLOAD_VERIFIER, $taskParams);
                $result['task'] = $task->getState();
            }
        }
        foreach($headers as $header => $value) {
            header($header . ': ' . $value);
        }
        // The array with the 'files' key is required by the fileupload plugin used for the frontend
        return $result;
    }

    /**
     * Cleans up and deletes a scheduled or finished task.
     * It's not possible to delete tasks that are currently running.
     *
     * @param $id
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete($id) {
        $this->assureAllowed('delete');
        // TODO Ensure that a user can only download his own task's results
        // Validation
        V::intVal()->check($id);
        $em = $this->getEntityManager();
        $task = $em->getRepository('HoneySens\app\models\entities\Task')->find($id);
        V::objectType()->check($task);
        // Running tasks can't be interrupted
        if($task->getStatus() == Task::STATUS_RUNNING) throw new BadRequestException();
        // Recursively remove temporary task files
        $result = $task->getResult();
        if($task->getStatus() == Task::STATUS_DONE && array_key_exists('path', $result)) {
            $dir = sprintf("%s/tasks/%s", $this->getConfig()['server']['data_path'], $id);
            $files = array_diff(scandir($dir), array('.','..'));
            foreach ($files as $file) (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
            rmdir($dir);
        }
        // Attempt to remove artifacts from the upload directory
        $params = $task->getParams();
        if(array_key_exists('path', $params)) {
            $uploadArtifact = sprintf('%s/%s/%s', $this->getConfig()['server']['data_path'], self::UPLOAD_PATH, $params['path']);
            if(file_exists($uploadArtifact)) unlink($uploadArtifact);
        }
        $em->remove($task);
        $em->flush();
    }
}