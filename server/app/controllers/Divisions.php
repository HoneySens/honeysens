<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\Division;
use HoneySens\app\models\entities\IncidentContact;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\NotFoundException;
use Respect\Validation\Validator as V;

class Divisions extends RESTResource {

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/api/divisions(/:id)/', function($id = null) use ($app, $em, $services, $config, $messages) {
            $controller = new Divisions($em, $services, $config);
            $criteria = array();
            $criteria['userID'] = $controller->getSessionUserID();
            $criteria['id'] = $id;
            try {
                $result = $controller->get($criteria);
            } catch(\Exception $e) {
                throw new NotFoundException();
            }
            echo json_encode($result);
        });

        $app->post('/api/divisions', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Divisions($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $divisionData = json_decode($request);
            $division = $controller->create($divisionData);
            echo json_encode($division->getState());
        });

        $app->put('/api/divisions/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Divisions($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $divisionData = json_decode($request);
            $division = $controller->update($id, $divisionData);
            echo json_encode($division->getState());
        });

        $app->delete('/api/divisions/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Divisions($em, $services, $config);
            $controller->delete($id);
            echo json_encode([]);
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
     * @param stdClass $contactData
     * @return IncidentContact
     * @throws BadRequestException
     */
    private function createContact($contactData) {
        // Validation
        V::attribute('email')
            ->attribute('user')
            ->attribute('type', V::intType()->between(0, 1))
            ->attribute('sendWeeklySummary', V::boolVal())
            ->attribute('sendCriticalEvents', V::boolVal())
            ->attribute('sendAllEvents', V::boolVal())
            ->attribute('sendSensorTimeouts', V::boolVal())
            ->check($contactData);
        $contact = new IncidentContact();
        if($contactData->type === IncidentContact::TYPE_MAIL) {
            V::attribute('email', V::email())->check($contactData);
            $contact->setEMail($contactData->email);
        } else {
            V::attribute('user', V::intVal())->check($contactData);
            $user = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\User')->find($contactData->user);
            V::objectType()->check($user);
            $contact->setUser($user);
        }
        $contact->setSendWeeklySummary($contactData->sendWeeklySummary)
            ->setSendCriticalEvents($contactData->sendCriticalEvents)
            ->setSendAllEvents($contactData->sendAllEvents)
            ->setSendSensorTimeouts($contactData->sendSensorTimeouts);
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
     * @param stdClass $contactData
     */
    private function updateContact(IncidentContact $contact, $contactData) {
        // Validation
        V::attribute('email')
            ->attribute('user')
            ->attribute('type', V::intType()->between(0, 1))
            ->attribute('sendWeeklySummary', V::boolVal())
            ->attribute('sendCriticalEvents', V::boolVal())
            ->attribute('sendAllEvents', V::boolVal())
            ->attribute('sendSensorTimeouts', V::boolVal())
            ->check($contactData);
        if($contactData->type === IncidentContact::TYPE_MAIL) {
            V::attribute('email', V::email())->check($contactData);
            $contact->setEMail($contactData->email);
            $contact->setUser();
        } else {
            V::attribute('user', V::intVal())->check($contactData);
            $user = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\User')->find($contactData->user);
            V::objectType()->check($user);
            $contact->setUser($user);
            $contact->setEMail(null);
        }
        $contact->setSendWeeklySummary($contactData->sendWeeklySummary)
            ->setSendCriticalEvents($contactData->sendCriticalEvents)
            ->setSendAllEvents($contactData->sendAllEvents)
            ->setSendSensorTimeouts($contactData->sendSensorTimeouts);
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
     * @param stdClass $data
     * @return Division
     */
    public function create($data) {
        $this->assureAllowed('create');
        // Validation
        V::objectType()
            ->attribute('name', V::alnum()->length(1, 255))
            ->attribute('users', V::arrayVal()->each(V::intType()))
            ->attribute('contacts', V::arrayVal()->each(V::objectType()))
            ->check($data);
        // Persistence
        $division = new Division();
        $division->setName($data->name);
        $em = $this->getEntityManager();
        $userRepository = $em->getRepository('HoneySens\app\models\entities\User');
        foreach($data->users as $userId) {
            $user = $userRepository->find($userId);
            V::objectType()->check($user);
            $user->addToDivision($division);
        }
        foreach($data->contacts as $contactData) {
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
     * @param stdClass $data
     * @return Division
     */
    public function update($id, $data) {
        $this->assureAllowed('update');
        // Validation
        V::intVal()->check($id);
        V::objectType()
            ->attribute('name', V::alnum()->length(1, 255))
            ->attribute('users', V::arrayVal()->each(V::intType()))
            ->attribute('contacts', V::arrayVal()->each(V::objectType()))
            ->check($data);
        // Persistence
        $em = $this->getEntityManager();
        $division = $em->getRepository('HoneySens\app\models\entities\Division')->find($id);
        V::objectType()->check($division);
        $division->setName($data->name);
        // Process user association
        $userRepository = $em->getRepository('HoneySens\app\models\entities\User');
        $tasks = $this->updateCollection($division->getUsers(), $data->users, $userRepository);
        foreach($tasks['add'] as $user) $user->addToDivision($division);
        foreach($tasks['remove'] as $user) $user->removeFromDivision($division);
        // Process contact association
        $contactRepository = $em->getRepository('HoneySens\app\models\entities\IncidentContact');
        $forUpdate = array();
        $toAdd = array();
        foreach($data->contacts as $contactData) {
            if(V::attribute('id')->validate($contactData)) $forUpdate[] = $contactData->id;
            else $toAdd[] = $contactData;
        }
        $tasks = $this->updateCollection($division->getIncidentContacts(), $forUpdate, $contactRepository);
        foreach($tasks['update'] as $contact)
            foreach($data->contacts as $contactData)
                if(V::attribute('id')->validate($contactData) && $contactData->id == $contact->getId())
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

    public function delete($id) {
        $this->assureAllowed('delete');
        // Validation
        V::intVal()->check($id);
        // Persistence
        $em = $this->getEntityManager();
        $division = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Division')->find($id);
        V::objectType()->check($division);
        $did = $division->getId();
        $sensorController = new Sensors($em, $this->getServiceManager(), $this->getConfig());
        foreach($division->getSensors() as $sensor) $sensorController->delete($did);
        $em->remove($division);
        $em->flush();
        $this->log(sprintf('Division %s (ID %d) and all associated users, sensors and events deleted', $division->getName(), $did), LogEntry::RESOURCE_DIVISIONS, $division->getId());
    }
}