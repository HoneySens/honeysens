<?php
namespace HoneySens\app\services\dto;

use HoneySens\app\models\constants\TransportEncryptionType;
use HoneySens\app\models\constants\TransportProtocol;

class SettingsParams {
    // The hostname the server is reachable as
    public string $serverHost;
    // TCP port the server offers its API on
    public int $serverPortHTTPS;
    // The delay between status update connection attempts initiated by sensors
    public int $sensorsUpdateInterval;
    // The internal network range that sensors should use for service containers
    public string $sensorsServiceNetwork;
    // Period (in minutes) that needs to pass after the last contact until a sensor is declared as 'offline'
    public int $sensorsTimeoutThreshold;
    // Specifies how many days the API log should be kept (if API log is enabled)
    public int $apiLogKeepDays;
    // If true, manager can move events to the archive, but not delete them
    public bool $preventEventDeletionByManagers;
    // If true, managers are not permitted to delete sensors
    public bool $preventSensorDeletionByManagers;
    // Forces users to enter a comment when editing events
    public bool $requireEventComment;
    // Forces users to enter a description when creating or updating event filters
    public bool $requireFilterDescription;
    // Instructs the client to preselect the "archive" checkbox by default when deleting events
    public bool $archivePrefer;
    // Specifies after how many days after their last modification events are moved into the archive
    public int $archiveMoveDays;
    // Specifies how many days archived events should be kept
    public int $archiveKeepDays;

    // Whether to send mail notification via SMTP
    public bool $smtpEnabled;
    // IP or hostname of a mail server
    public ?string $smtpServer = null;
    // TCP port to use for SMTP connections
    public ?int $smtpPort = null;
    // SMTP transport encryption
    public ?TransportEncryptionType $smtpEncryption = null;
    // E-Mail address to use as sender of system mails
    public ?string $smtpFrom = null;
    // SMTP Username to authenticate with
    public ?string $smtpUser = null;
    // SMTP Password to authenticate with. If not set, the existing password is kept.
    public ?string $smtpPassword;

    // Whether to authenticate users via LDAP
    public bool $ldapEnabled;
    // IP or hostname of an LDAP server
    public ?string $ldapServer = null;
    // TCP port to use for LDAP connections
    public ?int $ldapPort = null;
    // LDAP transport encryption (0: none, 1: STARTTLS, 2: TLS)
    public ?TransportEncryptionType $ldapEncryption = null;
    // LDAP template string
    public ?string $ldapTemplate = null;

    // Whether to forward events to a syslog-compatible server
    public bool $syslogEnabled;
    // IP or hostname of a syslog server
    public ?string $syslogServer = null;
    // Port to use for syslog connections
    public ?int $syslogPort = null;
    // Transport protocol to use for syslog connection (0: UDP, 1: TCP)
    public ?TransportProtocol $syslogTransport = null;
    // Facility according to syslog protocol (between 0 and 23)
    public ?int $syslogFacility = null;
    // Priority according to syslog protocol (2, 3, 4, 6, 7)
    public ?int $syslogPriority = null;
}