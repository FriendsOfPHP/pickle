<?php

namespace Pickle\Engine\Ini;

use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts;

class HHVM extends Abstracts\Engine\Ini implements Interfaces\Engine\Ini
{
    
    public function __construct(Interfaces\Engine $php)
    {
        parent::__construct($php);
    }

}

