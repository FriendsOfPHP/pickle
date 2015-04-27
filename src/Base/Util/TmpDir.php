<?php
namespace Pickle\Base\Util;

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

