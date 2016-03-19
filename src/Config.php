<?php

namespace Pickle;

use Composer;

/**
 * Class Config
 * @package Pickle
 */
class Config extends Composer\Config
{
    /**
     *
     */
    const DEFAULT_BASE_DIRNAME = '.pickle';

    /**
     * Config constructor.
     * @param bool $useEnvironment
     * @param null $baseDir
     */
    public function __construct($useEnvironment = true, $baseDir = null)
    {
        if ($useEnvironment === true) {
            $baseDir = $baseDir ?: (getenv('PICKLE_BASE_DIR') ?: null);
        }

        $baseDir = $baseDir ?: (getenv('HOME') ?: sys_get_temp_dir());
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::DEFAULT_BASE_DIRNAME;

        parent::__construct($useEnvironment, $baseDir);
    }
}
