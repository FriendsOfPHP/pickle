<?php
namespace Pickle;

use Symfony\Component\Console\Input\InputInterface as InputInterface;
use Symfony\Component\Console\Output\OutputInterface as OutputInterface;

class InstallerBinaryWindows
{
    use FileOps;

    private $php;
    private $ext_name;
    private $ext_version;
    private $progress = NULL;
    private $input = NULL;
    private $output = NULL;
    private $tempDir = NULL;

    public function __construct(PhpDetection $php, $ext)
    {
        /* used only if only the extension name is given*/
        if (strpos('//', $ext) !== false) {
            $this->extensionPeclExists();
        }

        $this->ext_name = $ext;
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
        $url = 'http://pecl.php.net/get/' . $this->ext_name;
        $headers = get_headers($url, 1);
        $status = $headers[0];
        if (strpos($status, '404')) {
            Throw new \Exception("Extension <$this->ext_name> cannot be found");
        }
    }

    private function findInLinks($url, $tofind)
    {
        $page = file_get_contents($url);
        $dom = new \DOMDocument();
        $dom->loadHTML($page);
        $files_a = $dom->getElementsByTagName('a');
        if (!$files_a) {
            return false;
        }
        $found = false;
        $v = false;
        foreach ($files_a as $a) {
            if ($a->nodeValue[0] == '[') {
                continue;
            }
            $v = trim($a->nodeValue);
            if ($tofind == $v) {
                $found = true;
                break;
            }
        }

        return $found ? $v : false;
    }

    private function fetchZipName($mode = 'release')
    {
        $php_vc = $this->php->getCompiler();
        $php_arch = $this->php->getArchitecture();
        $php_zts = $this->php->getZts() ? '-ts' : '-nts';
        $php_version = $this->php->getMajorVersion() . '.' .  $this->php->getMinorVersion();
        $pkg_version = $this->ext_version;

        $base_url = "http://windows.php.net/downloads/pecl/releases/";

        if (!$this->findInLinks($base_url . $this->ext_name, $pkg_version)) {
            Throw new \Exception('Binary for <' . $this->ext_name . '-' . $pkg_version . '> cannot be found');
        }

        $file_to_find = 'php_' . $this->ext_name . '-' . $pkg_version . '-' . $php_version . $php_zts . '-' . $php_vc . '-' . $php_arch . '.zip';
        $file_url = $this->findInLinks($base_url . $this->ext_name . '/' . $pkg_version, $file_to_find);

        if (!$file_url) {
            Throw new \Exception('Binary for <' . $file_to_find . '> cannot be found');
        }
        $url = $base_url . $this->ext_name . '/' . $pkg_version . '/' . $file_to_find;

        return $url;
    }

    private function uncompress($zipfile)
    {
        $this->createTempDir($this->ext_name);
        $this->cleanup();
        $za = new \ZipArchive();
        if ($za->open($zipfile) !== true || !$za->extractTo($this->tempDir)) {
            Throw new \Exception('Cannot extract Zip archive <' . $zipfile . '>');
        }
        $this->output->writeln("Extracting archives...");
        $za->extractTo($this->tempDir);
    }

    private function download($url)
    {
        $output = $this->output;
        $progress = $this->progress;

        $ctx = stream_context_create(array(), array('notification' => function ($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) use ($output, $progress) {
            switch ($notification_code) {
                case STREAM_NOTIFY_FILE_SIZE_IS:
                    $progress->start($output, $bytes_max);
                    break;
                case STREAM_NOTIFY_PROGRESS:
                    $progress->setCurrent($bytes_transferred);
                    break;
            }
        }));
        $output->writeln("downloading $url ");
        $file_contents = file_get_contents($url, false, $ctx);
        $progress->finish();
        if (!$file_contents) {
            Throw new \Exception('Cannot fetch <' . $url . '>');
        }
        $tmpdir = sys_get_temp_dir();
        $path = $tmpdir . '/' . $this->ext_name .'.zip';
        if (!file_put_contents($path, $file_contents)) {
            Throw new \Exception('Cannot save temporary file <' . $path . '>');
        }

        return $path;
    }

    private function copyFiles()
    {
        $DLLs = glob($this->tempDir . '/*.dll');
        foreach ($DLLs as $dll) {
            $dll = realpath($dll);
            $basename = basename($dll);
            if (substr($basename, 0, 4) == 'php_') {
                $dest = $this->php->getExtensionDir() . DIRECTORY_SEPARATOR . $basename;
                $this->output->writeln("copying $dll to " . $dest . "\n");
                $success = @copy($dll, $this->php->getExtensionDir() . '/' . $basename);
                if (!$success) {
                    Throw new \Exception('Cannot copy DLL <' . $dll . '> to <' . $dest . '>');
                }
            } else {
                $success = @copy($dll, dirname($this->php->getPhpCliPath()) . '/' . $basename);
                if (!$success) {
                    Throw new \Exception('Cannot copy DLL <' . $dll . '> to <' . $dest . '>');
                }
            }
        }
    }

    private function getInfoFromPecl()
    {
        $url = "http://pecl.php.net/get/" . $this->ext_name;
        $headers = get_headers($url);
        if (strpos($headers[0], '404') !== false) {
            Throw new \Exception('Cannot find extension <' . $this->ext_name . '>');
        }
        $header_pkg = false;
        foreach ($headers as $header) {
            if (strpos($header, 'tgz') !== false) {
                $header_pkg = $header;
                break;
            }
        }
        if ($header_pkg == false) {
            Throw new \Exception('Cannot find extension <' . $this->ext_name . '>');
        }
        $q1 = strpos($header_pkg, '"') + 1;
        $package_fullname = substr($header_pkg, $q1, strlen($header_pkg) - 2 - 3 - $q1);
        list($name, $version) = explode('-', $package_fullname);
        if ($name == '' || $version == '') {
            Throw new \Exception('Invalid response from pecl.php.net');
        }

        return [$name, $version];
    }

    public function install()
    {
        /*
        1. check if ext exists
        2. check if given version requested
            2.1 yes? check if builds available
            2.2 no? get latest version+build

        */
        list($this->ext_name, $this->ext_version) = $this->getInfoFromPecl();
        $url = $this->fetchZipName();
        $path_archive = $this->download($url);
        $this->uncompress($path_archive);
        $this->copyFiles();
        $this->cleanup();
    }
}
