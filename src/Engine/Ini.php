<?php

namespace Pickle\Engine;

use Pickle\Base\Interfaces;

class Ini
{
    protected static $instance = null;

    public static function factory(Interfaces\Engine $engine = null)
    {
        if (null == self::$instance) {
            $engine = null == $engine ? \Pickle\Engine::factory() : $engine;

            switch ($engine->getName()) {
                case 'php':
                    self::$instance = new PHP\Ini($engine);
                    break;

                case 'hhvm':
                    self::$instance = new HHVM\Ini($engine);
                    break;

                default:
                    throw new \Exception("Unsupported engine '{$engine->getName()}'");
            }
        }

        return self::$instance;
    }
}
