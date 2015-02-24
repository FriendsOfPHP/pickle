<?php

namespace Pickle\Base\Abstracts;

abstract class Engine
{
    public function getArchitecture()
    {
        /* Just a basic method, a concrete Engine implementation
        might need to override this. */
        $is_64_bit = 8 == PHP_INT_SIZE;

        if (defined("PHP_WINDOWS_VERSION_MAJOR")) {
            if ($is_64_bit) {
                return "x64";
            } else {
                return "x86";
            }
        } else {
            if ($is_64_bit) {
                return "x86_64";
            } else {
                return "i386";
            }
        }
    }

}

