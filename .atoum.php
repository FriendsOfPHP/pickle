<?php

use mageekguy\atoum\visibility;

$script->addTestsFromDirectory(__DIR__ . '/tests/units');
$script->noCodeCoverageForNamespaces('Composer');
$runner->addExtension(new \Atoum\PraspelExtension\Manifest());
$runner->addExtension(new visibility\extension($script));
