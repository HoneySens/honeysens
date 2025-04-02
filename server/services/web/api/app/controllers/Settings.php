<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\constants\TransportEncryptionType;
use HoneySens\app\models\constants\TransportProtocol;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\Utils;
use HoneySens\app\services\dto\SettingsParams;
use HoneySens\app\services\SettingsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Settings extends RESTResource {

    static function registerRoutes($api): void {
        $api->get('', [Settings::class, 'getSettings']);
        $api->put('', [Settings::class, 'updateSettings']);
        $api->post('/testmail', [Settings::class, 'sendtestMail']);
        $api->post('/testevent', [Settings::class, 'sendTestEvent']);
    }

    public function getSettings(Response $response, SettingsService $service): Response {
        $this->assureAllowed('get');
        $settings = $service->getSettings($this->getSessionUser()->role === UserRole::ADMIN);
        $response->getBody()->write(json_encode($settings));
        return $response;
    }

    public function updateSettings(Request $request, Response $response, SettingsService $service): Response {
        $this->assureAllowed('update');
        $settings = $service->updateSettings($this->validateSettingsParams($request->getParsedBody()));
        $response->getBody()->write(json_encode($settings));
        return $response;
    }

    public function sendtestMail(Request $request, Response $response, SettingsService $service): Response {
        $this->assureAllowed('update');
        $data = $request->getParsedBody();
        V::arrayType()
            ->key('recipient', Utils::emailValidator())
            ->key('smtpServer', V::stringType())
            ->key('smtpPort', V::intType()->between(0, 65535))
            ->key('smtpEncryption', V::intType()->between(0, 2))
            ->key('smtpUser', V::stringType())
            ->key('smtpFrom', Utils::emailValidator())
            ->key('smtpPassword', V::stringType())
            ->check($data);
        $task = $service->sendTestMail(
            $data['smtpFrom'],
            $data['recipient'],
            $data['smtpServer'],
            $data['smtpPort'],
            TransportEncryptionType::from(intval($data['smtpEncryption'])),
            $data['smtpUser'],
            $data['smtpPassword'],
            $this->getSessionUser());
        $response->getBody()->write(json_encode($task->getState()));
        return $response;
    }

    public function sendTestEvent(Response $response, SettingsService $service): Response {
        $this->assureAllowed('update');
        $response->getBody()->write(json_encode($service->sendTestEvent()));
        return $response;
    }

    public function validateSettingsParams(array $data): SettingsParams {
        $result = new SettingsParams();
        V::arrayType()
            ->key('serverHost', V::stringType())
            ->key('serverPortHTTPS', V::intType()->between(0, 65535))
            ->key('smtpEnabled', V::boolType())
            ->key('ldapEnabled', V::boolType())
            ->key('syslogEnabled', V::boolType())
            ->key('sensorsUpdateInterval', V::intType()->between(1, 60))
            ->key('sensorsServiceNetwork', V::regex('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(?:30|2[0-9]|1[0-9]|[1-9]?)$/'))
            ->key('sensorsTimeoutThreshold', V::intType()->between(1, 1440))
            ->key('apiLogKeepDays', V::intType()->between(0, 65535))
            ->key('preventEventDeletionByManagers', V::boolType())
            ->key('preventSensorDeletionByManagers', V::boolType())
            ->key('requireEventComment', V::boolType())
            ->key('requireFilterDescription', V::boolType())
            ->key('archivePrefer', V::boolType())
            ->key('archiveMoveDays', V::intType()->between(0, 65535))
            ->key('archiveKeepDays', V::intType()->between(0, 65535))
            ->key('ldapEncryption', V::intType()->between(0, 2))
            ->key('ldapPort', V::intType()->between(0, 65535))
            ->key('smtpEncryption', V::intType()->between(0, 2))
            ->key('smtpPort', V::intType()->between(0, 65535))
            ->key('syslogFacility', V::oneOf(V::intType()->between(0, 11), V::intType()->between(16, 23)))
            ->key('syslogPort', V::intType()->between(0, 65535))
            ->key('syslogPriority', V::oneOf(V::intType()->between(2, 4), V::intType()->between(6, 7)))
            ->key('syslogTransport', V::intType()->between(0, 1))
            ->check($data);
        $result->serverHost = $data['serverHost'];
        $result->serverPortHTTPS = $data['serverPortHTTPS'];
        $result->smtpEnabled = $data['smtpEnabled'];
        $result->ldapEnabled = $data['ldapEnabled'];
        $result->syslogEnabled = $data['syslogEnabled'];
        $result->sensorsUpdateInterval = $data['sensorsUpdateInterval'];
        $result->sensorsServiceNetwork = $data['sensorsServiceNetwork'];
        $result->sensorsTimeoutThreshold = $data['sensorsTimeoutThreshold'];
        $result->apiLogKeepDays = $data['apiLogKeepDays'];
        $result->preventEventDeletionByManagers = $data['preventEventDeletionByManagers'];
        $result->preventSensorDeletionByManagers = $data['preventSensorDeletionByManagers'];
        $result->requireEventComment = $data['requireEventComment'];
        $result->requireFilterDescription = $data['requireFilterDescription'];
        $result->archivePrefer = $data['archivePrefer'];
        $result->archiveMoveDays = $data['archiveMoveDays'];
        $result->archiveKeepDays = $data['archiveKeepDays'];
        if($result->smtpEnabled) {
            V::key('smtpServer', V::stringType())
                ->key('smtpFrom', Utils::emailValidator())
                ->key('smtpUser', V::stringType())
                ->key('smtpPassword', V::stringType()->length(0, 512))
                ->check($data);
        } else {
            V::key('smtpServer', V::optional(V::stringType()))
                ->key('smtpFrom', V::optional(Utils::emailValidator()))
                ->key('smtpUser', V::optional(V::stringType()))
                ->key('smtpPassword', V::optional(V::stringType()->length(0, 512)))
                ->check($data);
        }
        $result->smtpServer = $data['smtpServer'];
        $result->smtpPort = $data['smtpPort'];
        $result->smtpEncryption = TransportEncryptionType::from(intval($data['smtpEncryption']));
        $result->smtpFrom = $data['smtpFrom'];
        $result->smtpUser = $data['smtpUser'];
        $result->smtpPassword = $data['smtpPassword'];
        if($result->ldapEnabled) {
            V::key('ldapServer', V::stringType())
                ->key('ldapTemplate', V::stringType())
                ->check($data);
        } else {
            V::key('ldapServer', V::optional(V::stringType()))
                ->key('ldapTemplate', V::optional(V::stringType()))
                ->check($data);
        }
        $result->ldapServer = $data['ldapServer'];
        $result->ldapPort = $data['ldapPort'];
        $result->ldapEncryption = TransportEncryptionType::from(intval($data['ldapEncryption']));
        $result->ldapTemplate = $data['ldapTemplate'];
        if($result->syslogEnabled) {
            V::key('syslogServer', V::stringType())
                ->check($data);
        } else {
            V::key('syslogServer', V::optional(V::stringType()))
                ->check($data);
        }
        $result->syslogServer = $data['syslogServer'];
        $result->syslogPort = $data['syslogPort'];
        $result->syslogTransport = TransportProtocol::from($data['syslogTransport']);
        $result->syslogFacility = $data['syslogFacility'];
        $result->syslogPriority = $data['syslogPriority'];
        return $result;
    }
}
