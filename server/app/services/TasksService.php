<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use HoneySens\app\adapters\TaskAdapter;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\exceptions\SystemException;
use HoneySens\app\models\Utils;

class TasksService extends Service {

    const UPLOAD_PATH = 'upload';

    const UPLOAD_TYPE_SERVICE_ARCHIVE = 0;
    const UPLOAD_TYPE_PLATFORM_ARCHIVE = 1;

    private LogService $logger;
    private TaskAdapter $taskAdapter;

    public function __construct(EntityManager $em, LogService $logger, TaskAdapter $taskAdapter) {
        parent::__construct($em);
        $this->logger = $logger;
        $this->taskAdapter = $taskAdapter;
    }

    /**
     * Fetches tasks from the DB.
     *
     * @param User $user User for which to retrieve associated entities; admins receive all entities
     * @param int|null $id ID of a specific task to fetch
     * @throws NotFoundException
     */
    public function get(User $user, ?int $id = null): array {
        $qb = $this->em->createQueryBuilder();
        $qb->select('t')->from('HoneySens\app\models\entities\Task', 't');
        if($user->role !== UserRole::ADMIN) {
            $qb->join('t.user', 'u')
                ->andWhere('u.id = :user')
                ->setParameter('user', $user->getId());
        }
        try {
            if ($id !== null) {
                $qb->andWhere('t.id = :id')
                    ->setParameter('id', $id);
                return $qb->getQuery()->getSingleResult()->getState();
            } else {
                // Hide system tasks (that don't belong to any particular user)
                $qb->andWhere('t.user IS NOT NULL');
                $tasks = array();
                foreach ($qb->getQuery()->getResult() as $task) {
                    $tasks[] = $task->getState();
                }
                return $tasks;
            }
        } catch (NonUniqueResultException|NoResultException) {
            throw new NotFoundException();
        }
    }

    /**
     * Attempts to download a task result.
     * If there is no downloadable result, this will return an exception.
     * Returns a tuple [<local_result_path>, <file_name>, <callback_after_download>].
     *
     * @param User $sessionUser User as which to initiate the download. Only a task's owner or admins can download the result.
     * @param int $id Task ID to download
     * @param bool $delete If true, also delete the task resource after a successful download
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws SystemException
     */
    public function downloadResult(User $sessionUser, int $id, bool $delete=false): array {
        try {
            $task = $this->em->getRepository('HoneySens\app\models\entities\Task')->find($id);
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        if($task === null) throw new BadRequestException();
        if($task->getUser() !== $sessionUser && $sessionUser->role !== UserRole::ADMIN) throw new ForbiddenException();
        $result = $task->getResult();
        $tasksService = $this;
        $deleteFunc = function() use ($tasksService, $id, $sessionUser) {
            $tasksService->delete($sessionUser, $id);
        };
        if($task->getStatus() !== Task::STATUS_DONE || !array_key_exists('path', $result))
            throw new BadRequestException();
        $filepath = sprintf("%s/tasks/%s/%s", DATA_PATH, $id, $result['path']);
        if($delete) return [$filepath, $result['path'], $deleteFunc];
        else return [$filepath, $result['path'], null];
    }

    /**
     * Returns the number of tasks currently waiting in the task worker's job queue.
     */
    public function getBrokerQueueLength(): int {
        return $this->taskAdapter->getQueueLength();
    }

    /**
     * Application-wide generic upload endpoint.
     * Expects chunked uploads and launches a new verification task for each uploaded file.
     * Uploaded file data is read from $_FILES['fileBlob'] and typically a result of
     * POST requests with content-type "multipart/form-data" or "application/x-www-form-urlencoded".
     * Additional metadata is expected to be supplied with the request as POST vars:
     * - chunkIndex: The index of the currently transmitted chunk
     * - chunkCount: The total number of chunks available
     * - fileName: Original upload file name
     * - fileSize: Total size of the uploaded file
     *
     * Returns for individual chunks an array with the following keys:
     * - chunkIndex: Acknowledges the currently processed chunk
     * - append: Always true, expected by some frontend libraries to support chunked uploads
     *
     * After having successfully received the final chunk, the returned array also contains:
     * - task: Serialized upload verification Task state
     *
     * @param User $sessionUser User as which to initiate the upload. Will be owner of the resulting verification task.
     * @throws SystemException
     */
    public function upload(User $sessionUser): array {
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
        $this->logger->log(sprintf('File "%s" uploaded as "%s"', $fileName, $finalFileName), LogResource::TASKS);
        return $result;
    }

    /**
     * Cleans up and deletes a scheduled or finished task.
     * It's not possible to delete tasks that are currently running.
     *
     * @param User $sessionUser Session user that calls this service. Non-admin users can only delete their own tasks.
     * @param int $id ID of a task to delete
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws SystemException
     */
    public function delete(User $sessionUser, int $id): void {
        try {
            $task = $this->em->getRepository('HoneySens\app\models\entities\Task')->find($id);
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        if($task === null) throw new BadRequestException();
        if($task->getUser() !== $sessionUser && $sessionUser->role !== UserRole::ADMIN) throw new ForbiddenException();
        // Running tasks can't be interrupted
        if($task->getStatus() === Task::STATUS_RUNNING) throw new BadRequestException();
        // Recursively remove temporary task files
        $dir = sprintf("%s/tasks/%s", DATA_PATH, $id);
        if(($task->getStatus() === Task::STATUS_DONE || $task->getStatus() === Task::STATUS_ERROR) && file_exists($dir)) {
            Utils::recursiveDelete($dir);
        }
        // Attempt to remove artifacts from the upload directory
        $params = $task->getParams();
        if(array_key_exists('path', $params)) {
            $uploadArtifact = sprintf('%s/%s/%s', DATA_PATH, self::UPLOAD_PATH, $params['path']);
            if(file_exists($uploadArtifact)) unlink($uploadArtifact);
        }
        try {
            $this->em->remove($task);
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
    }
}
