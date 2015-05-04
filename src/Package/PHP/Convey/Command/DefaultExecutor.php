<?php

namespace Pickle\Package\PHP\Convey\Command;

use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts\Package\Convey;
use Pickle\Package\PHP;
use Pickle\Package\PHP\Util\PackageXml;

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

        if (null === $package && $no_convert) {
            throw new \RuntimeException('XML package are not supported. Please convert it before install');
        }

        if (null === $package) {
            $pkgXml = new PackageXml($target);
            $pkgXml->dump();

            $jsonPath = $pkgXml->getJsonPath();
            unset($package);

            $package = $jsonLoader->load($jsonPath);
        }

        $package->setRootDir($target);

        return $package;
    }
}
