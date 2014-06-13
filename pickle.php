#!/usr/bin/env php
<?php
// application.php
error_reporting(E_ALL);
ini_set("display_errors", true);
require_once __DIR__ . '/vendor/autoload.php';

use Pickle\Console\Command\ValidateCommand;
use Pickle\Console\Command\ConvertCommand;
use Pickle\Console\Command\ArchiveCommand;
use Pickle\Console\Command\InstallerCommand;

use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new ValidateCommand);
$application->add(new ConvertCommand);
$application->add(new ArchiveCommand);
$application->add(new InstallerCommand);
$application->run();
