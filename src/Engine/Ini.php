<?php

namespace Pickle\Engine;

use Pickle\Engine\Ini;

class Ini
{
	protected static $instance = NULL;

	public static function factory($engine = NULL)
	{
		if (NULL == self::$instance) {
			$engine = NULL == $engine ? \Pickle\Engine::factory() : $engine;

			switch ($engine->getName()) {
				case "php":
					self::$instance = new Ini\PHP($engine);
					break;

				case "hhvm":
					self::$instance = new Ini\HHVM($engine);
					break;

				default:
					throw new \Exception("Unsupported engine '{$engine->getName()}'");
			}
		}
		
		return self::$instance;
	}
}

