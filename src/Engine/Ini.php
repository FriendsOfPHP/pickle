<?php

namespace Pickle\Engine;

use Pickle\Engine\PHP;
use Pickle\Engine\HHVM;
use Pickle\Base\Interfaces;

class Ini
{
    protected static $instance = NULL;

    public static function factory(Interfaces\Engine $engine = NULL)
    {
        if (NULL == self::$instance) {
            $engine = NULL == $engine ? \Pickle\Engine::factory() : $engine;

            switch ($engine->getName()) {
                case "php":
                    self::$instance = new PHP\Ini($engine);
                    break;

                case "hhvm":
                    self::$instance = new HHVM\Ini($engine);
                    break;

                default:
                    throw new \Exception("Unsupported engine '{$engine->getName()}'");
            }
        }
        
        return self::$instance;
    }
}

