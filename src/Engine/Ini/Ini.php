<?php

namespace Pickle\Engine\Ini;

interface Ini
{
    public function __construct(\Pickle\Engine\Engine $php);
    public function updatePickleSection(array $dlls);
    public function getEngine();
}

