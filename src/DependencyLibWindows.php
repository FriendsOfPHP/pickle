<?php

namespace Pickle;

use Pickle\PhpDetection;

class DependencyLibWindows
{
    const dllMapUrl = 'http://windows.php.net/downloads/pecl/deps/dllmapping.json';
    private $dllMap = null;
    private $php;
    const deplisterUrl = 'http://windows.php.net/downloads/pecl/tools/deplister.exe';

    public function __construct(PhpDetection $php)
    {
        $this->php = $php;
        $this->checkDepListerExe();
        $this->fetchDllMap();
    }

    private function fetchDllMap()
    {
        if (is_null($this->dllMap)) {
            $data = @file_get_contents(DependencyLibWindows::dllMapUrl);
            if (!$data) {
                throw new \RuntimeException('Cannot fetch the DLL mapping file');
            }
            $dllMap = json_decode($data);
            if (!$dllMap) {
                throw new \RuntimeException('Cannot parse the DLL mapping file');
            }
        }
        $compiler = $this->php->getCompiler();
        $architecture = $this->php->getArchitecture();
        $this->dllMap = $dllMap->{$compiler}->{$architecture};

        return true;
    }

    private function checkDepListerExe()
    {
        $ret = exec('deplister.exe ' . $this->php->getPhpCliPath() . ' .');
        if (empty($ret)) {
            $depexe = @file_get_contents(DependencyLibWindows::deplisterUrl);
            if (!$depexe) {
                throw new \RuntimeException('Cannot fetch deplister.exe');
            }
            $dir = dirname($this->php->getPhpCliPath());
            $path = $dir . DIRECTORY_SEPARATOR . 'deplister.exe';
            if (!@file_put_contents($path, $depexe)) {
                throw new \RuntimeException('Cannot copy deplister.exe to ' . $dir);
            }
        }
    }

    private function getDllsForBinary($binary)
    {
        $out = [];
        $ret = exec('deplister ' . escapeshellarg($binary) . ' .', $out);
        if (empty($ret) || !$ret) {
            throw new \RuntimeException('Error while running deplister.exe');
        }
        $dlls = [];
        foreach ($out as $l) {
            list($dllname, $found) = explode(',', $l);
            $found = trim($found);
            $dllname = trim($dllname);
            $dlls[$dllname] = $found == 'OK' ? true : false;
        }

        return $dlls;
    }

    public function getZipUrlsForDll($binary)
    {
        $dll = $this->getDllsForBinary($binary);
        $packages = [];
        foreach ($this->dllMap as $pkg_name => $pkg) {
            foreach ($dll as $dll_name => $dll_installed) {
                if (in_array($dll_name, $pkg)) {
                    $packages[] = $pkg_name;
                    continue 2;
                }
            }
        }

        return $packages;
    }
}
