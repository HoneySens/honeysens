<?php
namespace HoneySens\app\models\constants;

enum LogResource: int {
    case GENERIC = 0;
    case CONTACTS = 1;
    case DIVISIONS = 2;
    case EVENTFILTERS = 3;
    case EVENTS = 4;
    case PLATFORMS = 5;
    case SENSORS= 6;
    case SERVICES = 7;
    case SETTINGS = 8;
    case TASKS = 9;
    case USERS = 10;
    case SYSTEM = 11;
    case SESSIONS = 12;
}
