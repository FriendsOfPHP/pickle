<?php

namespace Pickle;

use Pickle\Engine\HHVM;
use Pickle\Engine\PHP;

class Engine
{
    protected static $instance = null;

    public static function factory()
    {
        if (null == self::$instance) {
            if (defined('HHVM_VERSION')) {
                /* This needs to be checked first, PHP_VERSION is
                   defined in HHVM. */
                self::$instance = new HHVM();
            } else {
                /* We don't support anything else, so this has to
                   be classic PHP right now. This could change
                   if other PHP implementations are supported. */
                self::$instance = new PHP();
            }
        }

        return self::$instance;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
