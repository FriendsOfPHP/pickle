<?php
namespace Pickle\Package\PHP\Util\JSON;

use Pickle\Package\PHP;

class Dumper extends PHP\Util\Dumper
{
    /**
     * @param \Pickle\Base\Interfaces\Package $package
     *
     * @return string
     */
    public function dump(PHP\Package $package)
    {
        return json_encode(parent::dump($package), JSON_PRETTY_PRINT);
    }

    /**
     * @param \Pickle\Base\Interfaces\Package $package
     * @param string  $path
     */
    public function dumpToFile(PHP\Package $package, $path)
    {
        file_put_contents($path, $this->dump($package));
    }
}
