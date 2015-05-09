<?php

namespace Pickle\Package\Util\JSON;

use Pickle\Base\Interfaces;
use Pickle\Package\Util;

class Dumper
{
    /**
     * @param \Pickle\Base\Interfaces\Package $package
     *
     * @return string
     */
    public function dump(Interfaces\Package $package)
    {
        return json_encode((new Util\Dumper())->dump($package), JSON_PRETTY_PRINT);
    }

    /**
     * @param \Pickle\Base\Interfaces\Package $package
     * @param string                          $path
     */
    public function dumpToFile(Interfaces\Package $package, $path)
    {
        file_put_contents($path, $this->dump($package));
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
