<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\Mapping\MappingException;
use HoneySens\app\controllers\Divisions;
use HoneySens\app\models\constants\ContactType;
use HoneySens\app\models\entities\Division;
use HoneySens\app\models\entities\IncidentContact;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\exceptions\SystemException;
use HoneySens\app\models\Utils;

class DivisionsService {

    private EntityManager $em;
    private EventsService $eventsService;
    private LogService $logger;
    private SensorsService $sensorsService;

    public function __construct(EntityManager $em, LogService $logger, EventsService $eventsService, SensorsService $sensorsService) {
        $this->em= $em;
        $this->eventsService = $eventsService;
        $this->logger = $logger;
        $this->sensorsService = $sensorsService;
    }

    /**
     * Fetches divisions from the DB.
     *
     * @param User $user User for which to retrieve associated entities; admins receive all entities
     * @param int|null $id ID of a specific division to fetch
     * @throws NotFoundException
     */
    public function get(User $user, ?int $id = null): array {
        $qb = $this->em->createQueryBuilder();
        $qb->select('d')->from('HoneySens\app\models\entities\Division', 'd');
        if($user->getRole() !== User::ROLE_ADMIN) {
            $qb->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        try {
            if ($id !== null) {
                $qb->andWhere('d.id = :id')
                    ->setParameter('id', $id);
                return $qb->getQuery()->getSingleResult()->getState();
            } else {
                $divisions = array();
                foreach ($qb->getQuery()->getResult() as $division) {
                    $divisions[] = $division->getState();
                }
                return $divisions;
            }
        } catch (NonUniqueResultException|NoResultException) {
            throw new NotFoundException();
        }
    }

    /**
     * Creates a new division.
     *
     * @param string $name Name of the new division
     * @param array $users List of initial user IDs to add to the division
     * @param array $contacts List of initial contacts to add. Each item is an array specifying contact data.
     * @return Division
     * @throws BadRequestException
     * @throws SystemException
     */
    public function create(string $name, array $users, array $contacts): Division {
        // Name duplication check
        if($this->getDivisionByName($name) != null) throw new BadRequestException(Divisions::ERROR_DUPLICATE);
        // Persistence
        $division = new Division();
        $division->setName($name);
        $userRepository = $this->em->getRepository('HoneySens\app\models\entities\User');
        foreach($users as $userId) {
            $user = $userRepository->find($userId);
            if($user === null) throw new BadRequestException();
            $user->addToDivision($division);
        }
        try {
            foreach ($contacts as $contactData) {
                $contactType = ContactType::from($contactData['type']);
                $contact = $this->createContact(
                    $contactType,
                    $contactData['sendWeeklySummary'],
                    $contactData['sendCriticalEvents'],
                    $contactData['sendAllEvents'],
                    $contactData['sendSensorTimeouts'],
                    email: $contactType === ContactType::EMAIL ? $contactData['email'] : null,
                    userID: $contactType === ContactType::USER ? $contactData['user'] : null
                );
                $division->addIncidentContact($contact);
                $this->em->persist($contact);
            }
            $this->em->persist($division);
            $this->em->flush();
        } catch (ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Division %s (ID %d) created with %d users and %d contacts',
            $division->getName(), $division->getId(), count($division->getUsers()), count($division->getIncidentContacts())),
            LogEntry::RESOURCE_DIVISIONS, $division->getId());
        return $division;
    }

    /**
     * Updates an existing division.
     *
     * @param int $id Division ID to update
     * @param string $name Division name
     * @param array $users List of user IDs that should be associated with the division
     * @param array $contacts List of contacts that should be associated with the division. Each item is another array specifying contact data.
     * @throws BadRequestException
     * @throws SystemException
     */
    public function update(int $id, string $name, array $users, array $contacts): Division {
        $division = $this->em->getRepository('HoneySens\app\models\entities\Division')->find($id);
        if($division === null) throw new BadRequestException();
        // Name duplication check
        $duplicate = $this->getDivisionByName($name);
        if($duplicate !== null && $duplicate->getId() !== $division->getId())
            throw new BadRequestException(Divisions::ERROR_DUPLICATE);
        // Persistence
        $division->setName($name);
        // Process user association
        $userRepository = $this->em->getRepository('HoneySens\app\models\entities\User');
        $tasks = Utils::updateCollection($division->getUsers(), $users, $userRepository);
        foreach($tasks['add'] as $user) $user->addToDivision($division);
        foreach($tasks['remove'] as $user) $user->removeFromDivision($division);
        // Process contact association
        $contactRepository = $this->em->getRepository('HoneySens\app\models\entities\IncidentContact');
        $forUpdate = array();
        $toAdd = array();
        foreach($contacts as $contactData) {
            if(array_key_exists('id', $contactData)) $forUpdate[] = $contactData['id'];
            else $toAdd[] = $contactData;
        }
        $tasks = Utils::updateCollection($division->getIncidentContacts(), $forUpdate, $contactRepository);
        foreach($tasks['update'] as $contact)
            foreach($contacts as $contactData)
                if($contactData['id'] === $contact->getId()) {
                    $contactType = ContactType::from($contactData['type']);
                    $this->updateContact(
                        $contact,
                        $contactType,
                        $contactData['sendWeeklySummary'],
                        $contactData['sendCriticalEvents'],
                        $contactData['sendAllEvents'],
                        $contactData['sendSensorTimeouts'],
                        email: $contactType === ContactType::EMAIL ? $contactData['email'] : null,
                        userID: $contactType === ContactType::USER ? $contactData['user'] : null
                    );
                }
        try {
            foreach ($tasks['remove'] as $contact) {
                $division->removeIncidentContact($contact);
                $this->em->remove($contact);
            }
            foreach ($toAdd as $contactData) {
                $contactType = ContactType::from($contactData['type']);
                $contact = $this->createContact(
                    $contactType,
                    $contactData['sendWeeklySummary'],
                    $contactData['sendCriticalEvents'],
                    $contactData['sendAllEvents'],
                    $contactData['sendSensorTimeouts'],
                    email: $contactType === ContactType::EMAIL ? $contactData['email'] : null,
                    userID: $contactType === ContactType::USER ? $contactData['user'] : null
                );
                $division->addIncidentContact($contact);
                $this->em->persist($contact);
            }
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Division %s (ID %d) updated with %d users and %d contacts',
            $division->getName(), $division->getId(), count($division->getUsers()), count($division->getIncidentContacts())),
            LogEntry::RESOURCE_DIVISIONS, $division->getId());
        return $division;
    }

    /**
     * Removes a division with the given id.
     *
     * @param int $id Division ID to delete
     * @param bool $archive If set, all events of all sensors (of this division) are sent to the archive
     * @param User $user Session user that calls this service
     * @throws BadRequestException
     * @throws ForbiddenException
     */
    public function delete(int $id, bool $archive, User $user): void {
        $division = $this->em->getRepository('HoneySens\app\models\entities\Division')->find($id);
        if($division === null) throw new BadRequestException();
        // Delete sensors
        foreach($division->getSensors() as $sensor) {
            $this->sensorsService->delete($sensor->getId(), $archive, $user->getId(), $this, $this->eventsService);
        }
        try {
            // Remove division associations from archived events
            $this->em->createQueryBuilder()
                ->update('HoneySens\app\models\entities\ArchivedEvent', 'e')
                ->set('e.division', ':null')
                ->set('e.divisionName', ':dname')
                ->where('e.division = :division')
                ->setParameter('dname', $division->getName())
                ->setParameter('division', $division)
                ->setParameter('null', null)
                ->getQuery()
                ->execute();
            $this->em->remove($division);
            $this->em->flush();
            // Detach entities, otherwise we would run into conflicts with the now-detached ArchivedEvents
            $this->em->clear();
        } catch (ORMException|MappingException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Division %s (ID %d) and all associated users and sensors deleted. Events were %s.', $division->getName(), $division->getId(), $archive ? 'archived' : 'deleted'), LogEntry::RESOURCE_DIVISIONS, $division->getId());
    }

    /**
     * Asserts that a given user is associated with a specific division.
     * Throws an exception in case that affiliation doesn't exist.
     *
     * @param int $divisionID Division ID to check association for
     * @param int $userID User ID to check association for
     * @throws ForbiddenException
     */
    public function assureUserAffiliation(int $divisionID, int $userID): void {
        $qb = $this->em->createQueryBuilder();
        $qb->select('d')->from('HoneySens\app\models\entities\Division', 'd')
            ->where('d.id = :id')
            ->andwhere(':userid MEMBER OF d.users')
            ->setParameter('id', $divisionID)
            ->setParameter('userid', $userID);
        try {
            $qb->getQuery()->getSingleResult();
        } catch(\Exception) {
            throw new ForbiddenException();
        }
    }

    /**
     * Fetches IncidentContacts from the DB.
     *
     * @param User $user User for which to retrieve associated entities; admins receive all entities
     * @param int|null $id ID of a specific incident contact to fetch
     * @throws NotFoundException
     */
    public function getContact(User $user, ?int $id = null): array {
        $qb = $this->em->createQueryBuilder();
        $qb->select('c')->from('HoneySens\app\models\entities\IncidentContact', 'c');
        if($user->getRole() !== User::ROLE_ADMIN) {
            $qb->join('c.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        try {
            if ($id !== null) {
                $qb->andWhere('c.id = :id')
                    ->setParameter('id', $id);
                return $qb->getQuery()->getSingleResult()->getState();
            } else {
                $contacts = array();
                foreach ($qb->getQuery()->getResult() as $contact) {
                    $contacts[] = $contact->getState();
                }
                return $contacts;
            }
        } catch (NonUniqueResultException|NoResultException) {
            throw new NotFoundException();
        }
    }

    /**
     * Creates and returns, but does NOT persist a new incident contact.
     *
     * @param ContactType $type Indicates whether this contact is a user or just a plain E-Mail address
     * @param bool $sendWeeklySummary Determines if this contact should receive a weekly summary
     * @param bool $sendCriticalEvents Determines if this contact should receive critical event mails
     * @param bool $sendAllEvents Determines if this contact should receive mails for all events
     * @param bool $sendSensorTimeouts Determines if this contact should receive mails when sensors time out
     * @param string|null $email Depending on type, the E-Mail address that resembles this contact
     * @param int|null $userID Depending on type, the user ID that resembles this contact (its e-mail address will be used)
     * @throws BadRequestException
     */
    private function createContact(ContactType $type, bool $sendWeeklySummary, bool $sendCriticalEvents, bool $sendAllEvents, bool $sendSensorTimeouts, ?string $email, ?int $userID): IncidentContact {
        $contact = new IncidentContact();
        if($type === ContactType::EMAIL) {
            $contact->setEMail($email);
        } else {
            $user = $this->em->getRepository('HoneySens\app\models\entities\User')->find($userID);
            if($user === null) throw new BadRequestException();
            $contact->setUser($user);
        }
        $contact->setSendWeeklySummary($sendWeeklySummary)
            ->setSendCriticalEvents($sendCriticalEvents)
            ->setSendAllEvents($sendAllEvents)
            ->setSendSensorTimeouts($sendSensorTimeouts);
        return $contact;
    }

    /**
     * Updates, but does NOT persist an existing contact.
     *
     * @param IncidentContact $contact Incident contact entity to update
     * @param ContactType $type Indicates whether this contact is a user or just a plain E-Mail address
     * @param bool $sendWeeklySummary Determines if this contact should receive a weekly summary
     * @param bool $sendCriticalEvents Determines if this contact should receive critical event mails
     * @param bool $sendAllEvents Determines if this contact should receive mails for all events
     * @param bool $sendSensorTimeouts Determines if this contact should receive mails when sensors time out
     * @param string|null $email Depending on type, the E-Mail address that resembles this contact
     * @param int|null $userID Depending on type, the user ID that resembles this contact (its e-mail address will be used)
     * @throws BadRequestException
     */
    private function updateContact(IncidentContact $contact, ContactType $type, bool $sendWeeklySummary, bool $sendCriticalEvents, bool $sendAllEvents, bool $sendSensorTimeouts, ?string $email, ?int $userID): void {
        if($type === ContactType::EMAIL) {
            $contact->setEMail($email);
            $contact->setUser(null);
        } else {
            $user = $this->em->getRepository('HoneySens\app\models\entities\User')->find($userID);
            if($user === null) throw new BadRequestException();
            $contact->setUser($user);
            $contact->setEMail(null);
        }
        $contact->setSendWeeklySummary($sendWeeklySummary)
            ->setSendCriticalEvents($sendCriticalEvents)
            ->setSendAllEvents($sendAllEvents)
            ->setSendSensorTimeouts($sendSensorTimeouts);
    }

    /**
     * Fetches a division by its name.
     */
    private function getDivisionByName(string $name): ?Division {
        return $this->em->getRepository('HoneySens\app\models\entities\Division')->findOneBy(array('name' => $name));
    }
}
