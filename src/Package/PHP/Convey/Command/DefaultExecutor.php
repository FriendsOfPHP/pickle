<?php

namespace Pickle\Package\PHP\Convey\Command;

use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts\Package\Convey;
use Pickle\Package\PHP\Util\JSON\Dumper;
use Pickle\Package\PHP;


class DefaultExecutor implements Interfaces\Package\Convey\DefaultExecutor
{
    public function __construct(Interfaces\Package\Convey\Command $command)
    {

    }

    public function execute($target, $no_convert)
    {
        $jsonLoader = new PHP\Util\JSON\Loader(new PHP\Util\Loader());
        $pickle_json = $target . DIRECTORY_SEPARATOR . 'composer.json';
        $package = null;

        if (file_exists($pickle_json)) {
            $package = $jsonLoader->load($pickle_json);
        }

        if (null === $package && $no_convert) {
            throw new \RuntimeException('XML package are not supported. Please convert it before install');
        }

        if (null === $package) {
            if (file_exists($target . DIRECTORY_SEPARATOR . 'package2.xml')) {
                $pkg_xml = $target . DIRECTORY_SEPARATOR . 'package2.xml';
            } elseif (file_exists($target . DIRECTORY_SEPARATOR . 'package.xml')) {
                $pkg_xml = $target . DIRECTORY_SEPARATOR . 'package.xml';
            } else {
                throw new \Exception("package.xml not found");
            }

            $loader = new PHP\Util\XML\Loader(new PHP\Util\Loader());
            $package = $loader->load($pkg_xml);

            $dumper = new Dumper();
            $dumper->dumpToFile($package, $pickle_json);

            $package = $jsonLoader->load($pickle_json);
        }

        $package->setRootDir($target);

        return $package;
    }
}

