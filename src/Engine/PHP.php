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

namespace Pickle\Engine;

use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts;

class PHP extends Abstracts\Engine implements Interfaces\Engine
{
    private $phpCliEscaped;
    private $phpCli;
    private $phpize;
    private $version;
    private $major;
    private $minor;
    private $release;
    private $extra;
    private $compiler;
    private $architecture;
    private $zts;
    private $debug;
    private $iniPath;
    private $extensionDir;
    private $hasSdk;

    public function __construct($phpCli = PHP_BINARY)
    {
        if (!(is_file($phpCli) && is_executable($phpCli))) {
            throw new \Exception("Invalid php executable: $phpCli");
        }
        $this->phpCliEscaped = escapeshellcmd($phpCli);
        $this->phpCli = $phpCli;
        $this->getFromConstants();
    }

    private function getFromConstants()
    {
        $script = 'echo PHP_VERSION . \"\n\"; '.
                'echo PHP_MAJOR_VERSION . \"\n\"; '.
                'echo PHP_MINOR_VERSION . \"\n\"; '.
                'echo PHP_RELEASE_VERSION . \"\n\"; '.
                'echo PHP_EXTRA_VERSION . \"\n\"; '.
                'echo PHP_ZTS . \"\n\"; '.
                'echo PHP_DEBUG . \"\n\"; ';

        $cmd = $this->phpCliEscaped.' -r '.'"'.str_replace("\n", '', $script).'"';

        exec($cmd, $info);
        if (7 !== count($info)) {
            throw new \Exception('Could not determine info from the PHP binary');
        }

        list($this->version, $this->major, $this->minor, $this->release, $this->extra, $this->zts, $this->debug) = $info;
        $this->zts = (bool) $this->zts;

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            list($this->compiler, $this->architecture, $this->iniPath, $this->extensionDir) = $this->getFromPhpInfo();
        }
    }

    protected function getFullPathExtDir($dir)
    {
        $realpathDir = realpath($dir);
        $baseDir = dirname($realpathDir);
        $baseDirPhp = dirname($this->phpCli);
        if (empty($baseDir)) {
            if (empty($dir)) {
                return $baseDirPhp . 'ext';
            }
            return $baseDirPhp . '\\' .  $dir;
        }
        
        if ($baseDir == $baseDirPhp) {
            return $realpathDir;
        }
    }

    protected function getExtensionDirFromPhpInfo($info)
    {
        $extensionDir = '';

        foreach ($info as $s) {
            $pos_ext_dir = strpos($s, 'extension_dir');
            if (false !== $pos_ext_dir && substr($s, $pos_ext_dir - 1, 1) != '.') {
                list(, $extensionDir) = explode('=>', $s);
                break;
            }
        }

        $extensionDir = trim($extensionDir);
        if ('' == $extensionDir) {
            throw new \Exception('Cannot detect PHP extension directory');
        }
        return $this->getFullPathExtDir($extensionDir);
    }

    protected function getArchFromPhpInfo($info)
    {
        $arch = '';

        foreach ($info as $s) {
            if (false !== strpos($s, 'Architecture')) {
                list(, $arch) = explode('=>', $s);
                break;
            }
        }

        $arch = trim($arch);
        if ('' == $arch) {
            throw new \Exception('Cannot detect PHP build architecture');
        }

        return $arch;
    }

    protected function getIniPathFromPhpInfo($info)
    {
        $iniPath = '';

        foreach ($info as $s) {
            if (false !== strpos($s, 'Loaded Configuration File')) {
                list(, $iniPath) = explode('=>', $s);
                if ('(None)' === $iniPath) {
                    $iniPath = '';
                }

                break;
            }
        }

        $iniPath = trim($iniPath);
        if ('' == $iniPath) {
            throw new \Exception('Cannot detect php.ini directory');
        }

        return $iniPath;
    }

    protected function getCompilerFromPhpInfo($info)
    {
        $compiler = '';

        foreach ($info as $s) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                if (false !== strpos($s, 'PHP Extension Build')) {
                    list(, $build) = explode('=>', $s);
                    list(, , $compiler) = explode(',', $build);
                    $compiler = strtolower($compiler);
                    break;
                }
            } else {
                if (false !== strpos($s, 'Compiler')) {
                    list(, $compiler) = explode('=>', $s);
                    break;
                }
            }
        }

        $compiler = trim($compiler);
        if ('' == $compiler) {
            throw new \Exception('Cannot detect PHP build compiler version');
        }

        return $compiler;
    }

    private function getFromPhpInfo()
    {
        $cmd = $this->phpCliEscaped.' -i';
        exec($cmd, $info);

        if (!is_array($info)) {
            throw new \Exception('Cannot parse phpinfo output');
        }

        $arch = $this->getArchFromPhpInfo($info);
        $iniPath = $this->getIniPathFromPhpInfo($info);
        $extensionDir = $this->getExtensionDirFromPhpInfo($info);

        $compiler = strtolower($this->getCompilerFromPhpInfo($info));
        return [$compiler, $arch, $iniPath, $extensionDir];
    }

    public function getName()
    {
        return 'php';
    }

    public function hasSdk()
    {
        if (isset($this->hasSdk)) {
            return $this->hasSdk;
        }
        $cliDir = dirname($this->phpCli);
        $res = glob($cliDir.DIRECTORY_SEPARATOR.'phpize*');
        if (!$res) {
            $this->hasSdk = false;
        }
        $this->phpize = $res[0];

        return $this->hasSdk = false;
    }

    public function getArchitecture()
    {
        return $this->architecture;
    }

    public function getCompiler()
    {
        return $this->compiler;
    }

    public function getPath()
    {
        return $this->phpCli;
    }

    public function getMajorVersion()
    {
        return $this->major;
    }

    public function getMinorVersion()
    {
        return $this->minor;
    }

    public function getReleaseVersion()
    {
        return $this->release;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getZts()
    {
        return $this->zts;
    }

    public function getExtensionDir()
    {
        return $this->extensionDir;
    }

    public function getIniPath()
    {
        return $this->iniPath;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
