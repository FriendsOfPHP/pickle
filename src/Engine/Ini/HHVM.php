<?php

namespace Pickle\Engine\Ini;

use Pickle\Engine\Ini\Ini;
use Pickle\Engine\Ini\AbstractIni;

class HHVM extends AbstractIni implements Ini
{
	
	public function __construct(\Pickle\Engine\Engine $php)
	{
		parent::__construct($php);
	}

}

