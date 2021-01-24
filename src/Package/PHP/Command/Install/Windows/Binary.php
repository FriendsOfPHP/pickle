<?php

/**
 * Pickle
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2015-2015, Pickle community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Pickle\Package\PHP\Command\Install\Windows;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pickle\Base\Util\FileOps;
use Pickle\Engine;
use Pickle\Base\Archive;
use Pickle\Base\Util;

class Binary
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
    public function __construct($ext)
    {
        // used only if only the extension name is given
        if (strpos('//', $ext) !== false) {
            $this->extensionPeclExists();
        }

        $this->extName = $ext;
        $this->php = Engine::factory();
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
        $url = 'https://pecl.php.net/get/'.$this->extName;
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
        $opts = [
            'http' => [
                'header' => 'User-Agent: pickle'
            ],
        ];
        $context = stream_context_create($opts);
        $page = @file_get_contents($url, false, $context);
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
        $extName = strtolower($this->extName);
        $baseUrl = 'https://windows.php.net/downloads/pecl/releases/';

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
        $zipClass = Archive\Factory::getUnzipperClassName();
        $zipArchive = $zipClass($zipFile);
        /** @var \Pickle\Base\Interfaces\Archive\Unzipper $zipArchive */
        $this->output->writeln('Extracting archives...');
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
        $progress = $this->progress;
        $progress->setOverwrite(true);
        $ctx = stream_context_create(
            array(
                'http' => [
                    'header' => 'User-Agent: pickle'
                ]
            ),
            array(
                'notification' => function ($notificationCode, $severity, $message, $messageCode, $bytesTransferred, $bytesMax) use ($progress) {
                    switch ($notificationCode) {
                        case STREAM_NOTIFY_RESOLVE:
                        case STREAM_NOTIFY_AUTH_REQUIRED:
                        case STREAM_NOTIFY_COMPLETED:
                        case STREAM_NOTIFY_FAILURE:
                        case STREAM_NOTIFY_AUTH_RESULT:
                            break;
                
                        case STREAM_NOTIFY_REDIRECTED:
                            break;
                
                        case STREAM_NOTIFY_CONNECT:
                            break;
                
                        case STREAM_NOTIFY_FILE_SIZE_IS:
                            $progress->start($bytesMax);
                            break;
                
                        case STREAM_NOTIFY_MIME_TYPE_IS:
                            break;
                
                        case STREAM_NOTIFY_PROGRESS:
                            $progress->setProgress($bytesTransferred);
                            break;
                    };
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
                $success = copy($dll, $this->php->getExtensionDir().'/'.$basename);
                if (!$success) {
                    throw new \Exception('Cannot copy DLL <'.$dll.'> to <'.$dest.'>');
                }
            } else {
                $success = copy($dll, dirname($this->php->getPath()).'/'.$basename);
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
     *
     * @throws \Exception
     */
    private function getInfoFromPecl()
    {
        $url = 'https://pecl.php.net/get/'.$this->extName;
        $headers = get_headers($url);

        if (strpos($headers[0], '404') !== false) {
            throw new \Exception('Cannot find extension <'.$this->extName.'>');
        }
        $headerPkg = null;
        foreach ($headers as $header) {
            if (strpos($header, 'tgz') !== false) {
                $headerPkg = $header;
                break;
            }
        }
        if ($headerPkg === null) {
            throw new \Exception('Cannot find extension <'.$this->extName.'>');
        }

        if (!preg_match("|=(.*)\.[a-z0-9]{2,3}$|", $headerPkg, $m)) {
            throw new \Exception('Invalid response from pecl.php.net');
        }
        $packageFullname = $m[1];

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
     *  2.2 no? get latest version+build.
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

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
