<?php

$script->addTestsFromDirectory(__DIR__ . '/tests/units');
$runner->addExtension(new \Atoum\PraspelExtension\Manifest());
