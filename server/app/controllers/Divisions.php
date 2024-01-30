<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\Division;
use HoneySens\app\models\entities\IncidentContact;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Divisions extends RESTResource {

    const ERROR_DUPLICATE = 1;

    static function registerRoutes($app, $em, $services, $config) {
        $app->get('/api/divisions[/{id:\d+}]', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Divisions($em, $services, $config);
            $criteria = array();
            $criteria['userID'] = $controller->getSessionUserID();
            $criteria['id'] = $args['id'] ?? null;
            try {
                $result = $controller->get($criteria);
            } catch(\Exception $e) {
                throw new NotFoundException();
            }
            $response->getBody()->write(json_encode($result));
            return $response;
        });

        $app->post('/api/divisions', function(Request $request, Response $response) use ($app, $em, $services, $config) {
            $controller = new Divisions($em, $services, $config);
            $division = $controller->create($request->getParsedBody());
            $response->getBody()->write(json_encode($division->getState()));
            return $response;
        });

        $app->put('/api/divisions/{id:\d+}', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Divisions($em, $services, $config);
            $division = $controller->update($args['id'], $request->getParsedBody());
            $response->getBody()->write(json_encode($division->getState()));
            return $response;
        });

        $app->delete('/api/divisions/{id:\d+}', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Divisions($em, $services, $config);
            $controller->delete($args['id'], $request->getParsedBody());
            $response->getBody()->write(json_encode([]));
            return $response;
        });
    }

    /**
     * Creates and returns a new Contact entity with the provided attributes.
     * Caution: The presence of the fields 'email' or 'user' specify the contact type. Only one can be set at once.
     * - email: A plain E-Mail address string that resembles this contact
     * - user: The user ID that resembles this contact (his E-Mail address will be used)
     * - type: Indicates whether this contact is a user (1) or just a plain E-Mail address (0).
     * - sendWeeklySummary: A boolean value that determines if this contact should receive a weekly summary
     * - sendCriticalEvents: A boolean value that determines if this contact should receive critical event mails
     * - sendAllEvents: A boolean value that determines if this contact should receive mails for all events
     * - sendSensorTimeouts: A boolean value that determines if this contact should receive mails when sensors time out
     *
     * @param array $contactData
     * @return IncidentContact
     * @throws BadRequestException
     */
    private function createContact($contactData) {
        // Validation
        V::key('email')
            ->key('user')
            ->key('type', V::intType()->between(0, 1))
            ->key('sendWeeklySummary', V::boolVal())
            ->key('sendCriticalEvents', V::boolVal())
            ->key('sendAllEvents', V::boolVal())
            ->key('sendSensorTimeouts', V::boolVal())
            ->check($contactData);
        $contact = new IncidentContact();
        if($contactData['type'] === IncidentContact::TYPE_MAIL) {
            V::key('email', Utils::emailValidator())->check($contactData);
            $contact->setEMail($contactData['email']);
        } else {
            V::key('user', V::intVal())->check($contactData);
            $user = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\User')->find($contactData['user']);
            V::objectType()->check($user);
            $contact->setUser($user);
        }
        $contact->setSendWeeklySummary($contactData['sendWeeklySummary'])
            ->setSendCriticalEvents($contactData['sendCriticalEvents'])
            ->setSendAllEvents($contactData['sendAllEvents'])
            ->setSendSensorTimeouts($contactData['sendSensorTimeouts']);
        return $contact;
    }

    /**
     * Updates an existing contact entity with the provided attributes.
     * Caution: The presence of the fields 'email' or 'user' specify the contact type. Only one can be set at once.
     * - email: A plain E-Mail address string that resembles this contact
     * - user: The user ID that resembles this contact (his E-Mail address will be used)
     * - type: Indicates whether this contact is a user (1) or just a plain E-Mail address (0).
     * - sendWeeklySummary: A boolean value that determines if this contact should receive a weekly summary
     * - sendCriticalEvents: A boolean value that determines if this contact should receive critical event mails
     * - sendAllEvents: A boolean value that determines if this contact should receive mails for all events
     * - sendSensorTimeouts: A boolean value that determines if this contact should receive mails when sensors time out
     *
     * @param IncidentContact $contact
     * @param array $contactData
     */
    private function updateContact(IncidentContact $contact, $contactData) {
        // Validation
        V::key('email')
            ->key('user')
            ->key('type', V::intType()->between(0, 1))
            ->key('sendWeeklySummary', V::boolVal())
            ->key('sendCriticalEvents', V::boolVal())
            ->key('sendAllEvents', V::boolVal())
            ->key('sendSensorTimeouts', V::boolVal())
            ->check($contactData);
        if($contactData['type'] === IncidentContact::TYPE_MAIL) {
            V::key('email', Utils::emailValidator())->check($contactData);
            $contact->setEMail($contactData['email']);
            $contact->setUser();
        } else {
            V::key('user', V::intVal())->check($contactData);
            $user = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\User')->find($contactData['user']);
            V::objectType()->check($user);
            $contact->setUser($user);
            $contact->setEMail(null);
        }
        $contact->setSendWeeklySummary($contactData['sendWeeklySummary'])
            ->setSendCriticalEvents($contactData['sendCriticalEvents'])
            ->setSendAllEvents($contactData['sendAllEvents'])
            ->setSendSensorTimeouts($contactData['sendSensorTimeouts']);
    }

    /**
     * Fetches divisions from the DB by various criteria:
     * - userID: return only divisions that belong to the user with the given id
     * - id: return the divison with the given id
     * If no criteria are given, all divisons are returned.
     *
     * @param array $criteria
     * @return array
     */
    public function get($criteria) {
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('d')->from('HoneySens\app\models\entities\Division', 'd');
        if(V::key('userID', V::intType())->validate($criteria)) {
            $qb->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        if(V::key('id', V::intVal())->validate($criteria)) {
            $qb->andWhere('d.id = :id')
                ->setParameter('id', $criteria['id']);
            return $qb->getQuery()->getSingleResult()->getState();
        } else {
            $divisions = array();
            foreach($qb->getQuery()->getResult() as $division) {
                $divisions[] = $division->getState();
            }
            return $divisions;
        }
    }

    /**
     * Creates and persists a new Division object.
     * The following parameters are required:
     * - name: Division name
     * - users: Array specifying a list of user IDs that are part of the division
     * - contacts: Array specifying a list of contacts to add. Each item is another array specifying contact data.
     *
     * @param array $data
     * @return Division
     */
    public function create($data) {
        $this->assureAllowed('create');
        // Validation
        V::arrayType()
            ->key('name', V::alnum()->length(1, 255))
            ->key('users', V::arrayVal()->each(V::intType()))
            ->key('contacts', V::arrayVal()->each(V::arrayType()))
            ->check($data);
        // Name duplication check
        if($this->getDivisionByName($data['name']) != null) throw new BadRequestException(Divisions::ERROR_DUPLICATE);
        // Persistence
        $division = new Division();
        $division->setName($data['name']);
        $em = $this->getEntityManager();
        $userRepository = $em->getRepository('HoneySens\app\models\entities\User');
        foreach($data['users'] as $userId) {
            $user = $userRepository->find($userId);
            V::objectType()->check($user);
            $user->addToDivision($division);
        }
        foreach($data['contacts'] as $contactData) {
            $contact = $this->createContact($contactData);
            $division->addIncidentContact($contact);
            $em->persist($contact);
        }
        $em->persist($division);
        $em->flush();
        $this->log(sprintf('Division %s (ID %d) created with %d users and %d contacts',
            $division->getName(), $division->getId(), count($division->getUsers()), count($division->getIncidentContacts())),
            LogEntry::RESOURCE_DIVISIONS, $division->getId());
        return $division;
    }

    /**
     * Updates an existing Division object.
     * The following parameters are required:
     * - name: Division name
     * - users: Array specifying a list of user IDs that are part of the division
     * - contacts: Array specifying a list of contacts to add. Each item is another array specifying contact data.
     *
     * @param int $id
     * @param array $data
     * @return Division
     */
    public function update($id, $data) {
        $this->assureAllowed('update');
        // Validation
        V::intVal()->check($id);
        V::arrayType()
            ->key('name', V::alnum()->length(1, 255))
            ->key('users', V::arrayVal()->each(V::intType()))
            ->key('contacts', V::arrayVal()->each(V::arrayType()))
            ->check($data);
        $em = $this->getEntityManager();
        $division = $em->getRepository('HoneySens\app\models\entities\Division')->find($id);
        V::objectType()->check($division);
        // Name duplication check
        $duplicate = $this->getDivisionByName($data['name']);
        if($duplicate != null && $duplicate->getId() != $division->getId())
            throw new BadRequestException(Divisions::ERROR_DUPLICATE);
        // Persistence
        $division->setName($data['name']);
        // Process user association
        $userRepository = $em->getRepository('HoneySens\app\models\entities\User');
        $tasks = $this->updateCollection($division->getUsers(), $data['users'], $userRepository);
        foreach($tasks['add'] as $user) $user->addToDivision($division);
        foreach($tasks['remove'] as $user) $user->removeFromDivision($division);
        // Process contact association
        $contactRepository = $em->getRepository('HoneySens\app\models\entities\IncidentContact');
        $forUpdate = array();
        $toAdd = array();
        foreach($data['contacts'] as $contactData) {
            if(V::key('id')->validate($contactData)) $forUpdate[] = $contactData['id'];
            else $toAdd[] = $contactData;
        }
        $tasks = $this->updateCollection($division->getIncidentContacts(), $forUpdate, $contactRepository);
        foreach($tasks['update'] as $contact)
            foreach($data['contacts'] as $contactData)
                if(V::key('id')->validate($contactData) && $contactData['id'] == $contact->getId())
                    $this->updateContact($contact, $contactData);
        foreach($tasks['remove'] as $contact) {
            $division->removeIncidentContact($contact);
            $em->remove($contact);
        }
        foreach($toAdd as $contactData) {
            $contact = $this->createContact($contactData);
            $division->addIncidentContact($contact);
            $em->persist($contact);
        }
        $em->flush();
        $this->log(sprintf('Division %s (ID %d) updated with %d users and %d contacts',
            $division->getName(), $division->getId(), count($division->getUsers()), count($division->getIncidentContacts())),
            LogEntry::RESOURCE_DIVISIONS, $division->getId());
        return $division;
    }

    /**
     * Removes the division with the given id.
     * If 'archive' is set to true in additional criteria, all events of all sensors (of this division) are sent
     * to the archive first.
     *
     * @param int $id
     * @param array $criteria Additional deletion criteria
     * @throws ForbiddenException
     */
    public function delete($id, $criteria) {
        $this->assureAllowed('delete');
        // Validation
        $archive = V::key('archive', V::boolType())->validate($criteria) && $criteria['archive'];
        V::intVal()->check($id);
        // Persistence
        $em = $this->getEntityManager();
        $division = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Division')->find($id);
        V::objectType()->check($division);
        $did = $division->getId();
        // Delete sensors
        $sensorController = new Sensors($em, $this->getServiceManager(), $this->getConfig());
        foreach($division->getSensors() as $sensor) {
            $sensorController->delete($sensor->getId(), array('archive' => $archive));
        }
        // Remove division associations from archived events
        $em->createQueryBuilder()
            ->update('HoneySens\app\models\entities\ArchivedEvent', 'e')
            ->set('e.division', ':null')
            ->set('e.divisionName', ':dname')
            ->where('e.division = :division')
            ->setParameter('dname', $division->getName())
            ->setParameter('division', $division)
            ->setParameter('null', null)
            ->getQuery()
            ->execute();
        $em->remove($division);
        $em->flush();
        // Detach entities, otherwise we would run into conflicts with the now-detached ArchivedEvents
        $em->clear();
        $this->log(sprintf('Division %s (ID %d) and all associated users and sensors deleted. Events were %s.', $division->getName(), $did, $archive ? 'archived' : 'deleted'), LogEntry::RESOURCE_DIVISIONS, $division->getId());
    }

    private function getDivisionByName($name) {
        return $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Division')->findOneBy(array('name' => $name));
    }
}
