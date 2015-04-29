<?php

namespace Pickle\Package\PHP\Command\Release\Windows;

use Pickle\Base\Interfaces;
use Pickle\Package;
use Pickle\Package\PHP\Util\PackageXml;
use Pickle\Package\Util\Header;

class Binary implements Interfaces\Package\Release
{
    /**
     * @var \Pickle\Base\Interfaces\Package
     */
    protected $pkg = null;

    /*
     * @var Closure
     */
    protected $cb = null;

    /*
     * @var bool
     */
    protected $noConvert = false;

    /**
     * Constructor
     *
     * @param string  $path
     * @param Closure $cb
     * @param bool    $noConvert
     */
    public function __construct($path, $cb = null, $noConvert = false)
    {
        $this->pkg       = $this->readPackage($path);
        $this->cb        = $cb;
        $this->noConvert = $noConvert;
    }

    protected function readPackage($path)
    {
        $jsonLoader = new Package\Util\JSON\Loader(new Package\Util\Loader());
        $package = null;

        if (file_exists($path.DIRECTORY_SEPARATOR.'composer.json')) {
            $package = $jsonLoader->load($path.DIRECTORY_SEPARATOR.'composer.json');
        }

        if (null === $package && $this->noConvert) {
            throw new \RuntimeException('XML package are not supported. Please convert it before install');
        }

        if (null === $package) {
            try {
                $loader = new Package\PHP\Util\XML\Loader(new Package\Util\Loader());

                $pkgXml = new PackageXml($path);
                $pkgXml->dump();

                $jsonPath = $pkgXml->getJsonPath();

                $package = $jsonLoader->load($jsonPath);
            } catch (Exception $e) {
                /* pass for now, be compatible */
            }
        }

        if (null == $package) {
            /* Just ensure it's correct, */
            throw new \Exception("Couldn't read package info at '$path'");
        }

        $package->setRootDir(realpath($path));

        (new Header\Version($package))->updateJSON();

        return $package;
    }

    /**
     * Create package
     */
    public function create(array $args = array())
    {
        if (!isset($args["build"]) || !($args["build"] instanceof Interfaces\Package\Build)) {
            throw new \Exception("Invalid or NULL object passed as Interfaces\Package\Build");
	}
    	$build = $args["build"];

	/* XXX this is probably not the best way to do it, but there is currently no other way */
	$info = array(
		"thread_safe" => NULL,
		"compiler"      => NULL,
		"arch"          => NULL,
		"version"       => NULL,
		"name"          => NULL,
	);
	$tmp = $build->getLog("configure");

	if (!preg_match(",Build type\s+\|\s+([a-zA-Z]+),", $tmp, $m)) {
		throw new \Exception("Couldn't determine the build thread safety");
	}
	$is_release = "Release" == $m[1];

	if (!preg_match(",Thread Safety\s+\|\s+([a-zA-Z]+),", $tmp, $m)) {
		throw new \Exception("Couldn't determine the build thread safety");
	}
	$info["thread_safe"] = strtolower($m[1]) == "yes";

	if (!preg_match(",Compiler\s+\|\s+MSVC(\d+),", $tmp, $m)) {
		throw new \Exception("Currently only MSVC is supported");
	}
	$info["compiler"] = "vc" . $m[1];

	if (!preg_match(",Architecture\s+\|\s+([a-zA-Z]+),", $tmp, $m)) {
		throw new \Exception("Couldn't determine the build architecture");
	}
	$info["arch"] = strtolower($m[1]) == "yes";

	$info["version"] = $build->getPackage()->getPrettyVersion();
	$info["name"] = $build->getPackage()->getName();

	$tmp_dir = $build->getTempDir();

	$build_dir = "x86" == $info["arch"] ? $tmp_dir : $tmp_dir . DIRECTORY_SEPARATOR . "x64";
	$build_dir .= DIRECTORY_SEPARATOR . ($is_release ? "Release" : "Debug");
	$build_dir .= ($info["thread_safe"] ? "_TS" : "");

        /* Various file paths to pack. */
        $composer_json = $tmp_dir . DIRECTORY_SEPARATOR . "composer.json";

        if (file_exists($tmp_dir . DIRECTORY_SEPARATOR . "LICENSE")) {
            $license = $tmp_dir . DIRECTORY_SEPARATOR . "LICENSE";
        } else if (file_exists($tmp_dir . DIRECTORY_SEPARATOR . "COPYING")) {
            $license = $tmp_dir . DIRECTORY_SEPARATOR . "COPYING";
        } else if (file_exists($tmp_dir . DIRECTORY_SEPARATOR . "LICENSE.md")) {
            $license = $tmp_dir . DIRECTORY_SEPARATOR . "LICENSE.md";
        } else if (file_exists($tmp_dir . DIRECTORY_SEPARATOR . "COPYING.md")) {
            $license = $tmp_dir . DIRECTORY_SEPARATOR . "COPYING.md";
	} else {
		throw new \Exception("Couldn't find LICENSE");
        }

        $readme = NULL;
        if (file_exists($tmp_dir . DIRECTORY_SEPARATOR . "README")) {
            $readme = $tmp_dir . DIRECTORY_SEPARATOR . "README";
        } else if (file_exists($tmp_dir . DIRECTORY_SEPARATOR . "README.md")) {
            $readme = $tmp_dir . DIRECTORY_SEPARATOR . "README.md";
        }

        $ext_dll = $build_dir . DIRECTORY_SEPARATOR . "php_" . $info["name"] . ".dll";
        if (!file_exists($ext_dll)) {
            throw new \Exception("Couldn't find extension DLL");
	}
       
        $ext_pdb = $build_dir . DIRECTORY_SEPARATOR . "php_" . $info["name"] . ".pdb";
        if (!file_exists($ext_pdb)) {
            $ext_pdb = NULL;
	}


	var_dump($info, $composer_json, $license, $readme, $ext_dll, $ext_pdb);

    }
}

