<?php

namespace Pickle\Base\Interfaces\Engine;

interface Ini
{
    public function __construct(\Pickle\Base\Interfaces\Engine $php);
    public function updatePickleSection(array $dlls);
    public function getEngine();
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
