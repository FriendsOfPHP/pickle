<?php

$script->addTestsFromDirectory(__DIR__ . '/tests/units');
$script->noCodeCoverageForNamespaces('Composer');
$runner->addExtension(new \Atoum\PraspelExtension\Manifest());
