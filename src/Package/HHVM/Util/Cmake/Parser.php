<?php

namespace Pickle\Package\HHVM\Util\Cmake;

use Composer\Package\Loader\LoaderInterface;

class Parser
{
    protected $loader;

    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    public function load($path)
    {
        if (false === is_file($path)) {
            throw new \InvalidArgumentException('File not found: '.$path);
        }

        $cont = file_get_contents($path);

        /* Only the ext name seems to be readable from cmake yet,
        anything else left a dummy. Need some way to gather this
               info in the future, where ever it's provided. */
        $package = [
            'name' => $this->getExtName($cont),
            'version' => '0.0.0',
            'stability' => 'alpha',
            'description' => 'no description',
        ];

        return $this->loader->load($package);
    }

    public function getExtName($cont)
    {
        $ret = null;

        if (preg_match(",HHVM_EXTENSION\(([^\s]+)\s+,", $cont, $m)) {
            $ret = $m[1];
        }

        if (!$ret) {
            throw new \Exception("Couldn't parse extension name");
        }

        return $ret;
    }
}
