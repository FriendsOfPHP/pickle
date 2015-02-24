<?php
namespace Pickle;

use Symfony\Component\Console\Input\InputInterface as InputInterface;
use Symfony\Component\Console\Output\OutputInterface as OutputInterface;

class InstallerBinaryWindows
{
    use FileOps;

    private $php;
    private $extName;
    private $extVersion;
    private $progress = null;
    private $input = null;
    private $output = null;
    private $extDll = null;

    /**
     * @param string $ext
     */
    public function __construct(\Pickle\Base\Interfaces\Engine $php, $ext)
    {
        // used only if only the extension name is given
        if (strpos('//', $ext) !== false) {
            $this->extensionPeclExists();
        }

        $this->extName = $ext;
        $this->php = $php;
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

    private function extensionPeclExists()
    {
        $url = 'http://pecl.php.net/get/'.$this->extName;
        $headers = get_headers($url, 1);
        $status = $headers[0];
        if (strpos($status, '404')) {
            throw new \Exception("Extension <$this->extName> cannot be found");
        }
    }

    /**
     * @param string $url
     */
    private function findInLinks($url, $toFind)
    {
        $page = @file_get_contents($url);
        if (!$page) {
            return false;
        }
        $dom = new \DOMDocument();
        $dom->loadHTML($page);
        $links = $dom->getElementsByTagName('a');
        if (!$links) {
            return false;
        }

        foreach ($links as $link) {
            if ($link->nodeValue[0] == '[') {
                continue;
            }
            $value = trim($link->nodeValue);
            if ($toFind == $value) {
                return $value;
            }
        }

        return false;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    private function fetchZipName()
    {
        $phpVc = $this->php->getCompiler();
        $phpArch = $this->php->getArchitecture();
        $phpZts = $this->php->getZts() ? '-ts' : '-nts';
        $phpVersion = $this->php->getMajorVersion().'.'.$this->php->getMinorVersion();
        $pkgVersion = $this->extVersion;
        $extName =  strtolower($this->extName);
        $baseUrl = "http://windows.php.net/downloads/pecl/releases/";

        if (false === $this->findInLinks($baseUrl.$extName, $pkgVersion)) {
            throw new \Exception('Binary for <'.$extName.'-'.$pkgVersion.'> cannot be found');
        }

        $fileToFind = 'php_'.$extName.'-'.$pkgVersion.'-'.$phpVersion.$phpZts.'-'.$phpVc.'-'.$phpArch.'.zip';
        $fileUrl = $this->findInLinks($baseUrl.$extName.'/'.$pkgVersion, $fileToFind);

        if (!$fileUrl) {
            throw new \Exception('Binary for <'.$fileToFind.'> cannot be found');
        }
        $url = $baseUrl.$extName.'/'.$pkgVersion.'/'.$fileToFind;

        return $url;
    }

    /**
     * @param string $zipFile
     *
     * @throws \Exception
     */
    private function uncompress($zipFile)
    {
        $this->createTempDir($this->extName);
        $this->cleanup();
        $zipArchive = new \ZipArchive();
        if ($zipArchive->open($zipFile) !== true || !$zipArchive->extractTo($this->tempDir)) {
            throw new \Exception('Cannot extract Zip archive <'.$zipFile.'>');
        }
        $this->output->writeln("Extracting archives...");
        $zipArchive->extractTo($this->tempDir);
    }

    /**
     * @param string $url
     *
     * @return string
     *
     * @throws \Exception
     */
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
        $tmpdir = sys_get_temp_dir();
        $path = $tmpdir.'/'.$this->extName.'.zip';
        if (!file_put_contents($path, $fileContents)) {
            throw new \Exception('Cannot save temporary file <'.$path.'>');
        }

        return $path;
    }

    /**
     * @throws \Exception
     */
    private function copyFiles()
    {
        $DLLs = glob($this->tempDir.'/*.dll');
        $this->extDll = [];
        foreach ($DLLs as $dll) {
            $dll = realpath($dll);
            $basename = basename($dll);
            $dest = $this->php->getExtensionDir().DIRECTORY_SEPARATOR.$basename;
            if (substr($basename, 0, 4) == 'php_') {
                $this->extDll[] = $basename;
                $this->output->writeln("copying $dll to ".$dest."\n");
                $success = @copy($dll, $this->php->getExtensionDir().'/'.$basename);
                if (!$success) {
                    throw new \Exception('Cannot copy DLL <'.$dll.'> to <'.$dest.'>');
                }
            } else {
                $success = @copy($dll, dirname($this->php->getPath()).'/'.$basename);
                if (!$success) {
                    throw new \Exception('Cannot copy DLL <'.$dll.'> to <'.$dest.'>');
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function updateIni()
    {
        $ini = \Pickle\Engine\Ini::factory($this->php);
        $ini->updatePickleSection($this->extDll);
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getInfoFromPecl()
    {
        $url = "http://pecl.php.net/get/".$this->extName;
        $headers = get_headers($url);
        if (strpos($headers[0], '404') !== false) {
            throw new \Exception('Cannot find extension <'.$this->extName.'>');
        }
        $headerPkg = false;
        foreach ($headers as $header) {
            if (strpos($header, 'tgz') !== false) {
                $headerPkg = $header;
                break;
            }
        }
        if ($headerPkg == false) {
            throw new \Exception('Cannot find extension <'.$this->extName.'>');
        }
        $q1 = strpos($headerPkg, '"') + 1;
        $packageFullname = substr($headerPkg, $q1, strlen($headerPkg) - 2 - 3 - $q1);
        list($name, $version) = explode('-', $packageFullname);
        if ($name == '' || $version == '') {
            throw new \Exception('Invalid response from pecl.php.net');
        }

        return [$name, $version];
    }

    /**
     *  1. check if ext exists
     *  2. check if given version requested
     *  2.1 yes? check if builds available
     *  2.2 no? get latest version+build
     *
     * @throws \Exception
     */
    public function install()
    {
        list($this->extName, $this->extVersion) = $this->getInfoFromPecl();
        $url = $this->fetchZipName();
        $pathArchive = $this->download($url);
        $this->uncompress($pathArchive);
        $this->copyFiles();
        $this->cleanup();
        $this->updateIni();
    }

    public function getExtDllPaths()
    {
        $ret = array();

        foreach ($this->extDll as $dll) {
            $ret[] = $this->php->getExtensionDir().DIRECTORY_SEPARATOR.$dll;
        }

        return $ret;
    }
}
