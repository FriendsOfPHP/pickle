#!/usr/bin/env php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// application.php
require_once __DIR__ . '/../vendor/autoload.php';

use Pickle\Console\Command\ValidateCommand;
use Symfony\Component\Console\Application;

use Pickle\Validate;

$application = new Application();
$application->add(new ValidateCommand);
$application->run();