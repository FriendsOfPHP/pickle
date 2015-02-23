<?php

namespace Pickle;

use Pickle\Engine\HHVM;
use Pickle\Engine\PHP;

class Engine
{
	protected static $instance = NULL;

	public static function factory()
	{
		if (NULL == self::$instance) {
			if (defined('HHVM_VERSION')) {
				/* This needs to be checked first, PHP_VERSION is
				   defined in HHVM. */
				self::$instance = new HHVM;
			} else {
				/* We don't support anything else, so this has to
				   be classic PHP right now. This could change
				   if other PHP implementations are supported. */
				self::$instance = new PHP;
			}
		}

		return self::$instance;
	}
}

