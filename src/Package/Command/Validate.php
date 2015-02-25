<?php

namespace Pickle\Package\Command;

use Pickle\Engine;
use Pickle\Package\PHP;
use Pickle\Package\HHVM;

class Validate
{
	public static function factory($path, $cb)
	{
		$ret = NULL;
		$engine = Engine::factory();

		switch($engine->getName()) {
			case "php":
				$ret = new PHP\Command\Validate($path, $cb);
				break;

			case "hhvm":
				throw new \Exception("Not implemented for engine '{$engine->getName()}'");
				break;	

			default:
				throw new \Exception("Unsupported engine '{$engine->getName()}'. Implement it!");
		}

		return $ret;
	}
}
