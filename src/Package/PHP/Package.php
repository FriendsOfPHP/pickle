<?php

/**
 * Pickle.
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

namespace Pickle\Package\PHP;

use Pickle\Base\Abstracts\Package as AbstractPackage;
use Pickle\Base\Interfaces\Package as PackageInterface;
use Pickle\Base\Util\GitIgnore;
use Exception;
use CallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Package extends AbstractPackage implements PackageInterface
{
    /**
     * @var string Package's root directory
     */
    protected $path;

    /**
     * Get the package's root directory.
     *
     * @return string
     */
    public function getRootDir()
    {
        return $this->path;
    }

    /**
     * Get the package's root directory.
     *
     * @return string
     *
     * @throws Exception
     */
    public function getSourceDir()
    {
        $path = $this->getRootDir();
        $release = $path.DIRECTORY_SEPARATOR.$this->getPrettyName().'-'.$this->getPrettyVersion();

        if (is_dir($release)) {
            $path = $release;
        }

        /* Do sub directory search */
        if (!$this->extConfigIsIn($path)) {
            $path = $this->locateSourceDirByExtConfig($path);

            if (null === $path) {
                throw new Exception('config*.(m4|w32) not found');
            }
        }

        return $path;
    }

    /**
     * Set the package's source directory, containing config.m4/config.w32.
     *
     * @param string $path
     */
    public function setRootDir($path)
    {
        $this->path = $path;
    }

    public function setStability($stability)
    {
        $this->stability = $stability;
    }

    /**
     * Get configure options.
     *
     * @return array
     *
     * @throws Exception
     */
    public function getConfigureOptions()
    {
        $options = [];

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $configFile = $this->getSourceDir().'/config.w32';

            if (!file_exists($configFile)) {
                throw new Exception('config.w32 not found');
            }

            $config = file_get_contents($configFile);

            return array_merge(
                $this->fetchArgWindows('ARG_WITH', $config),
                $this->fetchArgWindows('ARG_ENABLE', $config)
            );
        }

        $configs = glob($this->getSourceDir().'/'.'config*.m4');

        if (!$configs) {
            return $options;
        }

        foreach ($configs as $config) {
            $options = array_merge($options, $this->getConfigureOptionsFromFile($config));
        }

        return $options;
    }

    public function getConfigureOptionsFromFile($file)
    {
        $config = file_get_contents($file);

        return array_merge(
            $this->fetchArg('PHP_ARG_WITH', $config),
            $this->fetchArgAc('AC_ARG_WITH', $config),
            $this->fetchArg('PHP_ARG_ENABLE', $config),
            $this->fetchArgAc('AC_ARG_ENABLE', $config)
        );
    }

    /**
     * @param string $which
     * @param string $config
     *
     * @return array
     */
    protected function fetchArgWindows($which, $config)
    {
        $next = 0;
        $options = [];
        $type = false !== strpos($which, 'ENABLE')  ? 'enable' : 'with';

        while (false !== ($s = strpos($config, $which, $next))) {
            $s = strpos($config, '(', $s);
            $e = strpos($config, ')', $s + 1);
            $option = substr($config, $s + 1, $e - $s);

            $elems = explode(',', $option);
            array_walk($elems, function (&$a) {
                $a = str_replace([')', "'"], ['', ''], $a);
                $a = trim($a);
            });

            @list($name, $prompt, $default) = $elems;
            $name = str_replace('"', '', $name);
            $options[$name] = (object) [
                'prompt' => $prompt,
                'type' => $type,
                'default' => $default,
            ];

            $next = $e + 1;
        }

        return $options;
    }

    /**
     * @param string $which
     * @param string $config
     *
     * @return array
     */
    protected function fetchArgAc($which, $config)
    {
        $next = 0;
        $options = [];
        $type = false !== strpos($which, 'ENABLE')  ? 'enable' : 'with';
        while (false !== ($s = strpos($config, $which, $next))) {
            $default = true;
            $s = strpos($config, '(', $s);
            $e = strpos($config, ')', $s + 1);
            $option = substr($config, $s + 1, $e - $s);

            if ('enable' === $type) {
                $default = (false !== strpos($option, '-disable-')) ? true : false;
            } elseif ('with' === $type) {
                $default = (false !== strpos($option, '-without-')) ? true : false;
            }

            list($name, $desc) = explode(',', $option);

            $desc = preg_replace('/\s+/', ' ', trim($desc));
            $desc = trim(substr($desc, 1, strlen($desc) - 2));
            $s_a = strpos($desc, ' ');
            $desc = trim(substr($desc, $s_a));

            $options[$name] = (object) [
                'prompt' => $desc,
                'type' => $type,
                'default' => $default,
            ];

            $next = $e + 1;
        }

        return $options;
    }

    /**
     * @param string $which
     * @param string $config
     *
     * @return array
     */
    protected function fetchArg($which, $config)
    {
        $next = 0;
        $options = [];

        $type = false !== strpos($which, 'ENABLE') ? 'enable' : 'with';
        while (false !== ($s = strpos($config, $which, $next))) {
            $default = 'y';
            $s = strpos($config, '(', $s);
            $e = strpos($config, ')', $s + 1);
            $option = substr($config, $s + 1, $e - $s);

            list($name, $desc) = explode(',', $option);

            /* Description can be part of the 3rd argument */
            if (empty($desc) || $desc === '[]') {
                list($name, , $desc) = explode(',', $option);
                $desc = preg_replace('/\s+/', ' ', trim($desc));
                $desc = trim(substr($desc, 1, strlen($desc) - 2));
                $desc = trim(str_replace(['[', ']'], ['', ''], $desc));
                $s_a = strpos($desc, ' ');
                $desc = trim(substr($desc, $s_a));
            }

            if ('enable' === $type) {
                $default = (false !== strpos($option, '-disable-')) ? true : false;
            } elseif ('with' === $type) {
                $default = (false !== strpos($option, '-without-')) ? true : false;
            }
            $name = str_replace(['[', ']'], ['', ''], $name);
            $options[$name] = (object) [
                'prompt' => trim($desc),
                'type' => $type,
                'default' => $default,
            ];
            $next = $e + 1;
        }

        return $options;
    }

    /**
     * Get files, will not return gitignore files.
     *
     * @return CallbackFilterIterator
     */
    public function getFiles()
    {
        return new CallbackFilterIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->getSourceDir())
            ),
            new GitIgnore($this)
        );
    }

    public function getVersionFromHeader()
    {
        $headers = glob($this->path.DIRECTORY_SEPARATOR.'*.h');
        $versionDefine = 'PHP_'.strtoupper($this->getSimpleName()).'_VERSION';

        foreach ($headers as $header) {
            $contents = @file_get_contents($header);
            if (!$contents) {
                throw new Exception("Cannot read header <$header>");
            }

            $posVersion = strpos($contents, $versionDefine);
            if ($posVersion !== false) {
                $nl = strpos($contents, "\n", $posVersion);
                $versionLine = trim(substr($contents, $posVersion, $nl - $posVersion));
                list($versionDefine, $version) = explode(' ', $versionLine);
                $version = trim(str_replace('"', '', $version));
                break;
            }
        }

        if (empty($version)) {
            throw new Exception('No '.$versionDefine.' can be found');
        }

        return [trim($versionDefine), $version];
    }

    protected function extConfigIsIn($path)
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR') !== false) {
            return file_exists(realpath($path).DIRECTORY_SEPARATOR.'config.w32');
        }

        $r = glob("$path/config*.m4");

        return is_array($r) && !empty($r);
    }

    protected function locateSourceDirByExtConfig($path)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileObj) {
            if ($fileObj->isFile() && preg_match(',config*.(m4|w32),', $fileObj->getBasename())) {
                return $fileObj->getPath();
            }
        }
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
