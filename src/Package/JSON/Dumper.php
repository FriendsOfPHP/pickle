<?php
namespace Pickle\Package\JSON;

use Pickle\Package;

class Dumper extends Package\Dumper
{
    public function dump(Package $package)
    {
        return json_encode(parent::dump($package), JSON_PRETTY_PRINT);
    }

    /**
     * @param Package $package
     * @param string  $path
     */
    public function dumpToFile(Package $package, $path)
    {
        file_put_contents($path, $this->dump($package));
    }
}
