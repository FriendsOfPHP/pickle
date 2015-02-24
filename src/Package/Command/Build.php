<?php

namespace Pickle\Package\Command;

use Pickle\Base\Interfaces;
use Pickle\Engine;
use Pickle\Package\PHP;

class Build
{
	public static function factory(Interfaces\Package $package, $optionValue)
	{
		$ret = NULL;
		$engine = Engine::factory();

		switch($engine->getName()) {
			case "php":
				if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
					$ret = new PHP\Command\Build\Windows($package, $optionValue);
				}else {
					$ret = new PHP\Command\Build\Unix($package, $optionValue);

				}
				break;

			default:
				throw new \Exception("Unsupported engine '{$engine->getName()}'. Implement it!");
		}

		return $ret;
	}
}
