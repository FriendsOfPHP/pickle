<?php

namespace Pickle\Engine\HHVM;

use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts;

class Ini extends Abstracts\Engine\Ini implements Interfaces\Engine\Ini
{
    
    public function __construct(Interfaces\Engine $php)
    {
        parent::__construct($php);
    }

}

