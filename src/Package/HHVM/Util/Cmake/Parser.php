<?php
namespace Pickle\Package\HHVM\Util\Cmake;

use Composer\Package\Loader\LoaderInterface;
use Pickle\Package\HHVM;

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
            throw new \InvalidArgumentException('File not found: ' . $path);
        }

	$cont = file_get_contents($path);

	/* XXX this is a dummy yet */
        $package = [
            'name' => 'unknown',
            'version' => '1.2.3',
            'stability' => 'alpha',
            'description' => 'unknown',
        ];

	return $this->loader->load($package);
    }
}

