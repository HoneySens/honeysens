<?php
namespace HoneySens\app\controllers;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use FileUpload\FileUpload;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\ServiceManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;
use \Slim\Routing\RouteCollectorProxy;

class Tasks extends RESTResource {

    const UPLOAD_PATH = 'upload';

    const UPLOAD_TYPE_SERVICE_ARCHIVE = 0;
    const UPLOAD_TYPE_PLATFORM_ARCHIVE = 1;

    static function registerRoutes($tasks, $em, $services, $config) {
        $tasks->get('[/{id:\d+}]', function(Request $request, Response $response, array $args) use ($em, $services, $config) {
            $controller = new Tasks($em, $services, $config);
            $criteria = array('userID' => $controller->getSessionUserID(), 'id' => $args['id'] ?? null);
            try {
                $result = $controller->get($criteria);
            } catch(Exception $e) {
                throw new NotFoundException();
            }
            $response->getBody()->write(json_encode($result));
            return $response;
        });

        $tasks->get('/{id:\d+}/result[/{delete:\d+}]', function(Request $request, Response $response, array $args) use ($em, $services, $config) {
            $controller = new Tasks($em, $services, $config);
            $delete = $args['delete'] ?? 0;
            $controller->downloadResult($args['id'], boolval($delete));
        });

        $tasks->get('/status', function(Request $request, Response $response) use ($em, $services, $config) {
            $controller = new Tasks($em, $services, $config);
            try {
                $result = array('queue_length' => $controller->getBrokerQueueLength());
                $response->getBody()->write(json_encode($result));
                return $response;
            } catch(Exception $e) {
                throw new NotFoundException();
            }
        });

        // Generic endpoint to upload files, returns the ID of the associated verification task.
        $tasks->post('/upload', function(Request $requset, Response $response) use ($em, $services, $config) {
            $controller = new Tasks($em, $services, $config);
            $state = $controller->upload();
            $response->getBody()->write(json_encode($state));
            return $response;
        });

        $tasks->delete('/{id:\d+}', function(Request $request, Response $response, array $args) use ($em, $services, $config) {
            $controller = new Tasks($em, $services, $config);
            $controller->delete($args['id']);
            $response->getBody()->write(json_encode([]));
            return $response;
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
            // Hide system tasks (that don't belong to any particular user)
            $qb->andWhere('t.user IS NOT NULL');
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
        $this->assureAllowed('get');
        $task = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Task')->find($id);
        V::objectType()->check($task);
        if($task->getUser() != $this->getSessionUser()) throw new ForbiddenException();
        $result = $task->getResult();
        $controller = $this;
        $deleteFunc = function() use ($controller, $id) {
            $controller->delete($id);
        };
        if($task->getStatus() == Task::STATUS_DONE && array_key_exists('path', $result)) {
            $filepath = sprintf("%s/tasks/%s/%s", DATA_PATH, $id, $result['path']);
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
    public function getBrokerQueueLength() {
        $this->assureAllowed('get');
        return $this->getServiceManager()->get(ServiceManager::SERVICE_TASK)->getQueueLength();
    }

    /**
     * Application-wide upload endpoint.
     * Supports chunked uploads and launches a new verification task for each uploaded file.
     *
     * @return array
     * @throws ForbiddenException
     */
    public function upload() {
        $this->assureAllowed('upload');
        $uploadDir = realpath(sprintf('%s/%s', DATA_PATH, self::UPLOAD_PATH));
        $fileBlob = 'fileBlob';
        if(!isset($_FILES[$fileBlob]) || !isset($_POST['token']))
            throw new Exception('Invalid upload data');
        $tmpFileName = $_FILES[$fileBlob]['tmp_name'];
        // Generate a user-specific and upload-specific file name
        $sessionUser = $this->getSessionUser();
        $finalFileName = $sessionUser->getId() . '-' . md5($_POST['token']);
        $chunkIndex = $_POST['chunkIndex'];
        $chunkCount = $_POST['chunkCount'];
        $fileName = $_POST['fileName'];
        $fileSize = $_POST['fileSize'];
        $targetFile = $uploadDir . '/' . $finalFileName;
        $result = [
            'chunkIndex' => $chunkIndex,
            'append' => true
        ];
        if($chunkCount > 1) $targetFile .= '_' . str_pad($chunkIndex, 4, '0', STR_PAD_LEFT);
        if(!move_uploaded_file($tmpFileName, $targetFile))
            throw new Exception('Uploaded file could not be moved from temp to upload directory');
        $chunks = glob("{$uploadDir}/{$finalFileName}_*");
        if($chunkCount > 1 && count($chunks) < $chunkCount) {
            // Further chunks are required, acknowledge that the current chunk has been received
            return $result;
        }
        // In case a multi-chunk upload was received, assemble the final result
        $allChunksUploaded = $chunkCount > 1 && count($chunks) == $chunkCount;
        if($allChunksUploaded) {
            if($fileSize >= disk_free_space($uploadDir)) {
                foreach($chunks as $tmpFileName) if(file_exists($tmpFileName)) @unlink($tmpFileName);
                throw new Exception('Disk capacity exceeded');
            }
            $outFile = $uploadDir . '/' . $finalFileName;
            $handle = fopen($outFile, 'a+');
            foreach($chunks as $tmpFileName) fwrite($handle, file_get_contents($tmpFileName));
            foreach($chunks as $tmpFileName) @unlink($tmpFileName);
            fclose($handle);
        }
        // Verify archive content
        $task = $this->getServiceManager()->get(ServiceManager::SERVICE_TASK)->enqueue(
            $this->getSessionUser(),
            Task::TYPE_UPLOAD_VERIFIER,
            array('path' => $finalFileName));
        $result['task'] = $task->getState();
        $this->log(sprintf('File "%s" uploaded as "%s"', $fileName, $finalFileName), LogEntry::RESOURCE_TASKS);
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
        // Validation
        V::intVal()->check($id);
        $em = $this->getEntityManager();
        $task = $em->getRepository('HoneySens\app\models\entities\Task')->find($id);
        V::objectType()->check($task);
        if($task->getUser() != $this->getSessionUser()) throw new ForbiddenException();
        // Running tasks can't be interrupted
        if($task->getStatus() == Task::STATUS_RUNNING) throw new BadRequestException();
        // Recursively remove temporary task files
        $result = $task->getResult();
        $dir = sprintf("%s/tasks/%s", DATA_PATH, $id);
        if(($task->getStatus() == Task::STATUS_DONE || $task->getStatus() == Task::STATUS_ERROR) && file_exists($dir)) {
            $files = array_diff(scandir($dir), array('.','..'));
            foreach ($files as $file) (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
            rmdir($dir);
        }
        // Attempt to remove artifacts from the upload directory
        $params = $task->getParams();
        if(array_key_exists('path', $params)) {
            $uploadArtifact = sprintf('%s/%s/%s', DATA_PATH, self::UPLOAD_PATH, $params['path']);
            if(file_exists($uploadArtifact)) unlink($uploadArtifact);
        }
        $em->remove($task);
        $em->flush();
    }
}
