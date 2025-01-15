<?php
namespace HoneySens\app\models\constants;

enum TemplateType: int {
    case EMAIL_EVENT_NOTIFICATION = 0;
    case EMAIL_SENSOR_TIMEOUT = 1;
    case EMAIL_SUMMARY = 2;
    case EMAIL_CA_EXPIRATION = 3;
    case EMAIL_HIGH_SYSTEM_LOAD = 4;
}