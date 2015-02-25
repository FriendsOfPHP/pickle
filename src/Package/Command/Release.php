<?php

namespace Pickle\Package\Command;

use Pickle\Engine;
use Pickle\Package\PHP;
use Pickle\Package\HHVM;

class Release
{
	public static function factory($path, $cb, $noConvert = false)
	{
		$engine = Engine::factory();

		switch($engine->getName()) {
			case "php":
				return new PHP\Command\Release($path, $cb, $noConvert);
				break;

			case "hhvm":
				throw new \Exception("Not implemented for engine '{$engine->getName()}'");
				break;	

			default:
				throw new \Exception("Unsupported engine '{$engine->getName()}'. Implement it!");
		}
	}
}
