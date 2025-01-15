<?php
namespace HoneySens\app\services\dto;

use HoneySens\app\models\entities\User;

class EventFilterConditions {
    // Include only events that belong to the given User; admins receive all events
    public ?User $user = null;
    // Include only events that have a higher id than the given one
    public ?int $lastID = null;
    // Event attribute name to sort after (only together with sortOrder)
    public ?string $sortBy = null;
    // Sort order ('asc' or 'desc'), only together with sortBy
    public ?string $sortOrder = null;
    // Include only events associated with the given Division id
    public ?int $divisionID = null;
    // Include only events associated with the given Sensor id
    public ?int $sensorID = null;
    // Event classification filter (0 to 4)
    public ?int $classification = null;
    // Status filter (int array, 0 to 3)
    public ?array $status = null;
    // Timestamp, to specify the beginning of a filtering date range
    public ?int $fromTS = null;
    // Timestamp, to specify the end of a date range
    public ?int $toTS = null;
    // List of specific event IDs to filter for
    public ?array $list = null;
    // Search term to find events that contain the given string
    public ?string $filter = null;
    // Whether to fetch from archived events
    public bool $archived = false;
}