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

    /*
     * @var string
     */
    protected $zip_base_name;
 

    /*
     * @var Interfaces\Package\Build
     */
    protected $build;
 
    /**
     * Constructor.
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

    protected function getInfoFromPhpizeLog(Interfaces\Package\Build $build)
    {
        $ret = array(
            'php_major' => null,
        'php_minor' => null,
        'php_patch' => null,
        );

        $tmp = $build->getLog('phpize');
        if (!preg_match(",Rebuilding configure.js[\n\r\d:]+\s+(.+)[\n\r]+,", $tmp, $m)) {
            throw new \Exception("Couldn't determine PHP development SDK path");
        }
        $sdk = $m[1];

        $ver_header = file_get_contents("$sdk/include/main/php_version.h");

        if (!preg_match(",PHP_MAJOR_VERSION\s+(\d+),", $ver_header, $m)) {
            throw new \Exception("Couldn't determine PHP_MAJOR_VERSION");
        }
        $ret['php_major'] = $m[1];

        if (!preg_match(",PHP_MINOR_VERSION\s+(\d+),", $ver_header, $m)) {
            throw new \Exception("Couldn't determine PHP_MINOR_VERSION");
        }
        $ret['php_minor'] = $m[1];

        if (!preg_match(",PHP_RELEASE_VERSION\s+(\d+),", $ver_header, $m)) {
            throw new \Exception("Couldn't determine PHP_RELEASE_VERSION");
        }
        $ret['php_patch'] = $m[1];

        return $ret;
    }

    protected function getInfoFromConfigureLog(Interfaces\Package\Build $build)
    {
        $info = array(
        'thread_safe' => null,
        'compiler'      => null,
        'arch'          => null,
        'version'       => null,
        'name'          => null,
    );

        $tmp = $build->getLog('configure');

        if (!preg_match(",Build type\s+\|\s+([a-zA-Z]+),", $tmp, $m)) {
            throw new \Exception("Couldn't determine the build thread safety");
        }
        $is_release = 'Release' == $m[1];

        if (!preg_match(",Thread Safety\s+\|\s+([a-zA-Z]+),", $tmp, $m)) {
            throw new \Exception("Couldn't determine the build thread safety");
        }
        $info['thread_safe'] = strtolower($m[1]) == 'yes';

        if (!preg_match(",Compiler\s+\|\s+MSVC(\d+),", $tmp, $m)) {
            throw new \Exception('Currently only MSVC is supported');
        }
        $info['compiler'] = 'vc'.$m[1];

        if (!preg_match(",Architecture\s+\|\s+([a-zA-Z0-9]+),", $tmp, $m)) {
            throw new \Exception("Couldn't determine the build architecture");
        }
        $info['arch'] = $m[1];

        $info['version'] = $build->getPackage()->getPrettyVersion();
        $info['name'] = $build->getPackage()->getName();

        return $info;
    }

    /**
     * Create package.
     */
    public function create(array $args = array())
    {
        if (!isset($args['build']) || !($args['build'] instanceof Interfaces\Package\Build)) {
            throw new \Exception("Invalid or NULL object passed as Interfaces\Package\Build");
        }
        $this->build = $build = $args['build'];

        $info = array();
        $info = array_merge($info, $this->getInfoFromPhpizeLog($build));
        $info = array_merge($info, $this->getInfoFromConfigureLog($build));

        $this->zip_base_name = 'php_'.$info['name'].'-'
            .$info['version'].'-'
            .$info['php_major'].'.'
            .$info['php_minor'].'-'
            .($info['thread_safe'] ? 'ts' : 'nts').'-'
            .$info['compiler'].'-'
            .$info['arch'];

        $tmp_dir = $build->getTempDir();

        $tmp = $build->getLog('configure');
        if (preg_match(",Build dir:\s+([\:\-\.0-9a-zA-Z\\\\_]+),", $tmp, $m)) {
            if (preg_match(",^[a-z]\:\\\\,i", $m[1]) && is_dir($m[1])) {
                /* Parsed the fully qualified path */
                $build_dir = $m[1];
            } else {
                /* otherwise construct */
                $build_dir = $tmp_dir.DIRECTORY_SEPARATOR.$m[1];
            }
        } else {
            $build_dir = 'x86' == $info['arch'] ? $tmp_dir : $tmp_dir.DIRECTORY_SEPARATOR.'x64';
            $build_dir .= DIRECTORY_SEPARATOR.($is_release ? 'Release' : 'Debug');
            $build_dir .= ($info['thread_safe'] ? '_TS' : '');
        }

        /* Various file paths to pack. */
        $composer_json = $this->pkg->getRootDir().DIRECTORY_SEPARATOR.'composer.json';

        if (file_exists($tmp_dir.DIRECTORY_SEPARATOR.'LICENSE')) {
            $license = $tmp_dir.DIRECTORY_SEPARATOR.'LICENSE';
        } elseif (file_exists($tmp_dir.DIRECTORY_SEPARATOR.'COPYING')) {
            $license = $tmp_dir.DIRECTORY_SEPARATOR.'COPYING';
        } elseif (file_exists($tmp_dir.DIRECTORY_SEPARATOR.'LICENSE.md')) {
            $license = $tmp_dir.DIRECTORY_SEPARATOR.'LICENSE.md';
        } elseif (file_exists($tmp_dir.DIRECTORY_SEPARATOR.'COPYING.md')) {
            $license = $tmp_dir.DIRECTORY_SEPARATOR.'COPYING.md';
        } else {
            throw new \Exception("Couldn't find LICENSE");
        }

        $readme = null;
        if (file_exists($tmp_dir.DIRECTORY_SEPARATOR.'README')) {
            $readme = $tmp_dir.DIRECTORY_SEPARATOR.'README';
        } elseif (file_exists($tmp_dir.DIRECTORY_SEPARATOR.'README.md')) {
            $readme = $tmp_dir.DIRECTORY_SEPARATOR.'README.md';
        }

        $ext_dll = $build_dir.DIRECTORY_SEPARATOR.'php_'.$info['name'].'.dll';
        if (!file_exists($ext_dll)) {
            throw new \Exception("Couldn't find extension DLL");
        }

        $ext_pdb = $build_dir.DIRECTORY_SEPARATOR.'php_'.$info['name'].'.pdb';
        if (!file_exists($ext_pdb)) {
            $ext_pdb = null;
        }

        /* pack the outcome */
	$zip_name = "{$this->zip_base_name}.zip";

        $zip = new \ZipArchive();
        if (!$zip->open($zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            throw new \Exception("Failed to open '$zip_name' for writing");
        }

        $zip->addFile($composer_json, basename($composer_json));
        $zip->addFile($license, basename($license));
        $zip->addFile($ext_dll, basename($ext_dll));
        if ($readme) {
            $zip->addFile($readme, basename($readme));
        }
        if ($ext_pdb) {
            $zip->addFile($ext_pdb, basename($ext_pdb));
        }
        $zip->close();
    }

    public function packLog()
    {
        if (!$this->zip_base_name) {
            /* return silently, something hardl bad could have happened
	       in the creation phase so there are no logs at all. */
            return;
        }
        $this->build->packLog("{$this->zip_base_name}-logs.zip");
    }
}

