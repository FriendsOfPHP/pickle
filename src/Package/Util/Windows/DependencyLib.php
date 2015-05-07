<?php

namespace Pickle\Package\Util\Windows;

use Symfony\Component\Console\Input\InputInterface as InputInterface;
use Symfony\Component\Console\Output\OutputInterface as OutputInterface;
use Pickle\Base\Util\FileOps;
use Pickle\Base\Util;

class DependencyLib
{
    use FileOps;

    const DLL_MAP_URL = 'http://windows.php.net/downloads/pecl/deps/dllmapping.json';
    const DEPLISTER_URL = 'http://windows.php.net/downloads/pecl/tools/deplister.exe';
    const DEPS_URL = 'http://windows.php.net/downloads/pecl/deps';

    private $dllMap = null;
    private $php;

    private $progress = null;
    private $input = null;
    private $output = null;

    private $fetchedZips = array();

    public function __construct(\Pickle\Base\Interfaces\Engine $php)
    {
        $this->php = $php;
        $this->checkDepListerExe();
        $this->fetchDllMap();
    }

    private function fetchDllMap()
    {
        $dllMap = null;

        if (is_null($this->dllMap)) {
            $data = @file_get_contents(self::DLL_MAP_URL);
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
        if (!isset($dllMap->{$compiler}->{$architecture})) {
            /* Just for the case the given compiler/arch set isn't defined in the dllmap,
           or we've got a corrupted file, or ...
           The dllMap property should be ensured an array. */
            $this->dllMap = array();
        } else {
            $this->dllMap = $dllMap->{$compiler}->{$architecture};
        }

        return true;
    }

    private function checkDepListerExe()
    {
        $ret = exec('deplister.exe '.$this->php->getPath().' .');
        if (empty($ret)) {
            $depexe = @file_get_contents(self::DEPLISTER_URL);
            if (!$depexe) {
                throw new \RuntimeException('Cannot fetch deplister.exe');
            }
            $dir = dirname($this->php->getPath());
            $path = $dir.DIRECTORY_SEPARATOR.'deplister.exe';
            if (!@file_put_contents($path, $depexe)) {
                throw new \RuntimeException('Cannot copy deplister.exe to '.$dir);
            }
        }
    }

    private function getDllsForBinary($binary)
    {
        $out = [];
        $ret = exec('deplister.exe '.escapeshellarg($binary).' .', $out);
        if (empty($ret) || !$ret) {
            throw new \RuntimeException('Error while running deplister.exe');
        }
        $dlls = [];
        foreach ((array) $out as $l) {
            list($dllname, $found) = explode(',', $l);
            $found = trim($found);
            $dllname = trim($dllname);
            $dlls[$dllname] = $found == 'OK' ? true : false;
        }

        return $dlls;
    }

    public function getZipUrlsForDll($binary, $ignore_installed = false)
    {
        $dll = $this->getDllsForBinary($binary);
        $packages = [];
        foreach ($this->dllMap as $pkg_name => $pkg) {
            foreach ($dll as $dll_name => $dll_installed) {
                if (in_array($dll_name, $pkg)) {
                    if ($ignore_installed && $dll_installed) {
                        continue;
                    }
                    $packages[] = $pkg_name;
                    continue 2;
                }
            }
        }

        return $packages;
    }

    public function resolveForBin($dll, $resolve_multiple_cb = null)
    {
        /* XXX Change it to false and implement a kinda --force option for that. */
        $dep_zips = $this->getZipUrlsForDll($dll, false);

        if (count($dep_zips) == 1) {
            $dep_zip = $dep_zips[0];

            if (in_array($dep_zip, $this->fetchedZips)) {
                return true;
            }
        } elseif (count($dep_zips) > 1) {
            foreach ($dep_zips as $dep_zip) {
                /* The user has already picked one here, ignore it. */
            if (in_array($dep_zip, $this->fetchedZips)) {
                return true;
            }
            }
            if (null != $resolve_multiple_cb) {
                $dep_zip = $resolve_multiple_cb($dep_zips);
            } else {
                throw new \Extension("Multiple choice for dependencies, couldn't resolve");
            }
        } else {
            /* That might be not quite true, as we might just not have the
           corresponding dependency package. However it's fetched from
           the PECL build dependencies, no extension build should have
           been exist if there's no dependency package uploaded. */
           return true;
        }

        return $this->resolveForZip($dep_zip, $resolve_multiple_cb);
    }

    public function resolveForZip($zip_name, $resolve_multiple_cb = null)
    {
        if (in_array($zip_name, $this->fetchedZips)) {
            return true;
        }

        $url = self::DEPS_URL."/$zip_name";
        $path = $this->download($url);
        try {
            $this->uncompress($path);
            $lst = $this->copyFiles();
        } catch (\Exception $e) {
            $this->cleanup();
            throw new \Exception($e->getMessage());
        }
        $this->cleanup();
        $this->fetchedZips[] = $zip_name;

        foreach ($lst as $bin) {
            $this->resolveForBin($bin, $resolve_multiple_cb);
        }

        return true;
    }

    private function copyFiles()
    {
        $ret = array();
        $DLLs = glob($this->tempDir.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'*.dll');

    /* Copying ALL files from the zip, not just required. */
        foreach ($DLLs as $dll) {
            $dll = realpath($dll);
            $basename = basename($dll);
            $dest = dirname($this->php->getPath()).DIRECTORY_SEPARATOR.$basename;
            $success = @copy($dll, dirname($this->php->getPath()).'/'.$basename);
            if (!$success) {
                throw new \Exception('Cannot copy DLL <'.$dll.'> to <'.$dest.'>');
            }

            $ret[] = $dest;
        }

        return $ret;
    }

    private function download($url)
    {
        $output = $this->output;
        $progress = $this->progress;

        $ctx = stream_context_create(
            array(),
            array(
                'notification' => function ($notificationCode, $severity, $message, $messageCode, $bytesTransferred, $bytesMax) use ($output, $progress) {
                    switch ($notificationCode) {
                        case STREAM_NOTIFY_FILE_SIZE_IS:
                            $progress->start($output, $bytesMax);
                            break;
                        case STREAM_NOTIFY_PROGRESS:
                            $progress->setCurrent($bytesTransferred);
                            break;
                    }
                },
            )
        );
        $output->writeln("downloading $url ");
        $fileContents = file_get_contents($url, false, $ctx);
        $progress->finish();
        if (!$fileContents) {
            throw new \Exception('Cannot fetch <'.$url.'>');
        }
        $tmpdir = Util\TmpDir::get();
        $path = $tmpdir.DIRECTORY_SEPARATOR.basename($url);
        if (!file_put_contents($path, $fileContents)) {
            throw new \Exception('Cannot save temporary file <'.$path.'>');
        }

        return $path;
    }
    private function uncompress($zipFile)
    {
        $this->createTempDir();
        $this->cleanup();
        $zipArchive = new \ZipArchive();
        if ($zipArchive->open($zipFile) !== true || !$zipArchive->extractTo($this->tempDir)) {
            throw new \Exception('Cannot extract Zip archive <'.$zipFile.'>');
        }
        $this->output->writeln('Extracting archives...');
        $zipArchive->extractTo($this->tempDir);
    }

    public function setProgress($progress)
    {
        $this->progress = $progress;
    }

    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
