<?php

namespace Pickle\Package\Convey\Command;

interface Command
{
    public function execute($target, $no_convert);

    public function getType();
}

