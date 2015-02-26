<?php

namespace Pickle\Package\Command;

use Pickle\Engine;
use Pickle\Package\PHP;
use Pickle\Package\HHVM;

class Install
{
	public static function factory($path)
	{
		$engine = Engine::factory();

		switch($engine->getName()) {
			case "php":
				if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
					return new PHP\Command\Install\Windows\Binary($path);
				} else {
					throw new \Exception(
						"On Unix Build::factory() functionality should be used to implememnt installation, " .
						"except you really need to install a binary."
					);
				}

			case "hhvm":
				throw new \Exception("Not implemented for engine '{$engine->getName()}'");

			default:
				throw new \Exception("Unsupported engine '{$engine->getName()}'. Implement it!");
		}
	}
}
