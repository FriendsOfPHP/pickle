<?php

namespace Pickle\Package\Command;

use Pickle\Base\Interfaces;
use Pickle\Engine;
use Pickle\Package\PHP;
use Pickle\Package\HHVM;

class Build
{
	public static function factory(Interfaces\Package $package, $optionValue)
	{
		$engine = Engine::factory();

		switch($engine->getName()) {
			case "php":
				if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
					return new PHP\Command\Build\Windows($package, $optionValue);
				}else {
					return new PHP\Command\Build\Unix($package, $optionValue);

				}

			case "hhvm":
				throw new \Exception("Not implemented for engine '{$engine->getName()}'");
				break;	

			default:
				throw new \Exception("Unsupported engine '{$engine->getName()}'. Implement it!");
		}
	}
}
