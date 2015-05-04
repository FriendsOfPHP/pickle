<?php

namespace Pickle\Package\HHVM\Convey\Command;

use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts\Package\Convey;
use Pickle\Package\Util\JSON\Dumper;
use Pickle\Package\HHVM\Util\Cmake;

class DefaultExecutor implements Interfaces\Package\Convey\DefaultExecutor
{
    public function __construct(Interfaces\Package\Convey\Command $command)
    {
    }

    public function execute($target, $no_convert)
    {
        $jsonLoader = new \Pickle\Package\Util\JSON\Loader(new \Pickle\Package\Util\Loader());
        $pickle_json = $target.DIRECTORY_SEPARATOR.'composer.json';
        $package = null;

        if (file_exists($pickle_json)) {
            $package = $jsonLoader->load($pickle_json);
        }

        /* Do we really need to check this here? */
        /*if (null === $package && $no_convert) {
            throw new \RuntimeException('XML package are not supported. Please convert it before install');
        }*/

        if (null === $package) {
            $config_cmake = $target.DIRECTORY_SEPARATOR.'config.cmake';
            if (!file_exists($config_cmake)) {
                throw new \Exception('config.cmake not found');
            }

            $cmp = new Cmake\Parser(new \Pickle\Package\Util\Loader());
            $package = $cmp->load($config_cmake);

            $dumper = new Dumper();
            $dumper->dumpToFile($package, $pickle_json);

            $package = $jsonLoader->load($pickle_json);
        }

        $package->setRootDir($target);

        return $package;
    }
}
