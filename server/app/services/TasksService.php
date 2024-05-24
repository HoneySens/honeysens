<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use HoneySens\app\adapters\TaskAdapter;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\Utils;
use Respect\Validation\Validator as V;

class TasksService {

    const UPLOAD_PATH = 'upload';

    const UPLOAD_TYPE_SERVICE_ARCHIVE = 0;
    const UPLOAD_TYPE_PLATFORM_ARCHIVE = 1;

    private EntityManager $em;
    private LogService $logger;
    private TaskAdapter $taskAdapter;

    public function __construct(EntityManager $em, LogService $logger, TaskAdapter $taskAdapter) {
        $this->em= $em;
        $this->logger = $logger;
        $this->taskAdapter = $taskAdapter;
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
        $qb = $this->em->createQueryBuilder();
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
    public function downloadResult($id, User $sessionUser, bool $delete=false) {
        $task = $this->em->getRepository('HoneySens\app\models\entities\Task')->find($id);
        V::objectType()->check($task);
        if($task->getUser() != $sessionUser) throw new ForbiddenException();
        $result = $task->getResult();
        $tasksService = $this;
        $deleteFunc = function() use ($tasksService, $id, $sessionUser) {
            $tasksService->delete($id, $sessionUser);
        };
        if($task->getStatus() == Task::STATUS_DONE && array_key_exists('path', $result)) {
            $filepath = sprintf("%s/tasks/%s/%s", DATA_PATH, $id, $result['path']);
            if($delete) return [$filepath, $result['path'], $deleteFunc];
            else return [$filepath, $result['path'], null];
        } else throw new BadRequestException();
    }

    /**
     * Queries the task worker for availability.
     *
     * @return bool
     * @throws ForbiddenException
     */
    public function getBrokerQueueLength() {
        return $this->taskAdapter->getQueueLength();
    }

    /**
     * Application-wide upload endpoint.
     * Supports chunked uploads and launches a new verification task for each uploaded file.
     *
     * @return array
     * @throws ForbiddenException
     */
    public function upload(User $sessionUser) {
        $uploadDir = realpath(sprintf('%s/%s', DATA_PATH, self::UPLOAD_PATH));
        $fileBlob = 'fileBlob';
        if(!isset($_FILES[$fileBlob]) || !isset($_POST['token']))
            throw new \Exception('Invalid upload data');
        $tmpFileName = $_FILES[$fileBlob]['tmp_name'];
        // Generate a user-specific and upload-specific file name
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
            throw new \Exception('Uploaded file could not be moved from temp to upload directory');
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
                throw new \Exception('Disk capacity exceeded');
            }
            $outFile = $uploadDir . '/' . $finalFileName;
            $handle = fopen($outFile, 'a+');
            foreach($chunks as $tmpFileName) fwrite($handle, file_get_contents($tmpFileName));
            foreach($chunks as $tmpFileName) @unlink($tmpFileName);
            fclose($handle);
        }
        // Verify archive content
        $task = $this->taskAdapter->enqueue(
            $sessionUser,
            Task::TYPE_UPLOAD_VERIFIER,
            array('path' => $finalFileName));
        $result['task'] = $task->getState();
        $this->logger->log(sprintf('File "%s" uploaded as "%s"', $fileName, $finalFileName), LogEntry::RESOURCE_TASKS);
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
    public function delete($id, User $sessionUser) {
        // Validation
        V::intVal()->check($id);
        $task = $this->em->getRepository('HoneySens\app\models\entities\Task')->find($id);
        V::objectType()->check($task);
        if($task->getUser() != $sessionUser) throw new ForbiddenException();
        // Running tasks can't be interrupted
        if($task->getStatus() == Task::STATUS_RUNNING) throw new BadRequestException();
        // Recursively remove temporary task files
        $result = $task->getResult();
        $dir = sprintf("%s/tasks/%s", DATA_PATH, $id);
        if(($task->getStatus() == Task::STATUS_DONE || $task->getStatus() == Task::STATUS_ERROR) && file_exists($dir)) {
            Utils::recursiveDelete($dir);
        }
        // Attempt to remove artifacts from the upload directory
        $params = $task->getParams();
        if(array_key_exists('path', $params)) {
            $uploadArtifact = sprintf('%s/%s/%s', DATA_PATH, self::UPLOAD_PATH, $params['path']);
            if(file_exists($uploadArtifact)) unlink($uploadArtifact);
        }
        $this->em->remove($task);
        $this->em->flush();
    }
}
