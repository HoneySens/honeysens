<?php
namespace HoneySens\app\services;

enum ResponseFormat: string {
    case JSON = 'application/json';
    case CSV = 'text/csv';
}
