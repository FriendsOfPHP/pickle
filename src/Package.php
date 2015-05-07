<?php

namespace Pickle;

use Pickle\Package\PHP;
use Pickle\Package\HHVM;

class Package
{
    protected static $instance = null;

    protected static function deliverFresh($force)
    {
        if ($force && self::$instance || is_null(self::$instance)) {
            /* XXX does this leak the previous instance? */
            self::$instance = null;

            return true;
        }

        return false;
    }

    public static function factory($name, $version, $prettyVersion, $force = false)
    {
        if (self::deliverFresh($force)) {
            $engine = Engine::factory();

            switch ($engine->getName()) {
                case 'php':
                    self::$instance = new PHP\Package($name, $version, $prettyVersion);
                    break;

                case 'hhvm':
                    self::$instance = new HHVM\Package($name, $version, $prettyVersion);
                    break;

                default:
                    throw new \Exception("Unsupported engine '{$engine->getName()}'. Implement it!");
            }
        }

        return self::$instance;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
