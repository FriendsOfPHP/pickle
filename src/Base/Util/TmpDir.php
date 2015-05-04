<?php

namespace Pickle\Base\Util;

/* This is a completely static class to manage a global temporary dir.
   The temporary dir has to be setup from the --tmp-dir option in the
   corresponding command. */
class TmpDir
{
    public static $tmpDir;

    public static function get()
    {
        if (!self::$tmpDir) {
            self::$tmpDir = sys_get_temp_dir();
        }

        return self::$tmpDir;
    }

    public static function set($tmpDir, $create = false)
    {
        if (!$create) {
            if (!is_dir($tmpDir)) {
                throw new \Exception("Directory '$tmpDir' does not exist");
            }
        } else {
            if (!mkdir($tmpDir)) {
                throw new \Exception("Could not create temporary dir at '$tmpDir'");
            }
        }

        self::$tmpDir = $tmpDir;
    }
}
