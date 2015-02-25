<?php

namespace Pickle;

use Pickle\Engine;
use Pickle\Package\PHP;
use Pickle\Package\HHVM;


class Package
{
	protected static $instance = NULL;

	public static function factory($name, $version, $prettyVersion)
	{
		if (is_null(self::$instance)) {
			$engine = Engine::factory();
			switch($engine->getName()) {
				case "php":
					self::$instance = new PHP\Package($name, $version, $prettyVersion);
					break;

				case "hhvm":
					self::$instance = new HHVM\Package($name, $version, $prettyVersion);
					break;

				default:
					throw new \Exception("Unsupported engine '{$engine->getName()}'. Implement it!");
			}

		}

		return self::$instance;
	}
}

