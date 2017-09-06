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

namespace Pickle\Package\PHP\Command\Build;

use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts;
use Pickle\Engine;

class Windows extends Abstracts\Package\Build implements Interfaces\Package\Build
{
    public function prepare()
    {
        if (!file_exists('c:\\php-sdk\\bin')) {
            throw new \Exception('PHP SDK not found');
        }
        putenv('path=c:\\php-sdk\\bin;'.getenv('path'));

        if (!$this->runCommand('phpsdk_setvars')) {
            throw new \Exception('phpsdk_setvars failed');
        }

        $this->phpize();
    }

    /**
     * @param string $src
     */
    private function copySrcDir($src, $dest)
    {
        foreach (scandir($src) as $file) {
            $srcfile = rtrim($src, '/').'/'.$file;
            $destfile = rtrim($dest, '/').'/'.$file;
            if (!is_readable($srcfile)) {
                continue;
            }
            if ($file != '.' && $file != '..') {
                if (is_dir($srcfile)) {
                    if (!is_dir($destfile)) {
                        mkdir($destfile);
                    }
                    $this->copySrcDir($srcfile, $destfile);
                } else {
                    copy($srcfile, $destfile);
                }
            }
        }
    }

    public function phpize()
    {
        $backCwd = getcwd();
        chdir($this->pkg->getSourceDir());

        $res = $this->runCommand('phpize');
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('phpize failed');
        }
    }

    protected function prepareConfigOpts()
    {
        $configureOptions = '--enable-debug-pack';
        foreach ($this->options as $name => $option) {
            $decision = null;
            if ('enable' === $option->type) {
                $decision = true == $option->input ? 'enable' : 'disable';
            } elseif ('disable' == $option->type) {
                $decision = false == $option->input ? 'enable' : 'disable';
            }

            if (!is_null($decision)) {
                $configureOptions .= ' --'.$decision.'-'.$name;
            }
        }

        $php_prefix = dirname(Engine::factory()->getPath());
        $configureOptions .= " --with-prefix=$php_prefix";

        $this->appendPkgConfigureOptions($configureOptions);

        return $configureOptions;
    }

    public function configure($opts = null)
    {
        /* duplicate src tree to do not pollute repo or src dir */
        $this->copySrcDir($this->pkg->getSourceDir(), $this->tempDir);
        $backCwd = getcwd();
        chdir($this->tempDir);

        /* XXX check sanity */
        $configureOptions = $opts ? $opts : $this->prepareConfigOpts();

        $res = $this->runCommand($this->pkg->getSourceDir().'/configure '.$configureOptions);
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('configure failed, see log at '.$this->tempDir.'\config.log');
        }

        /* This post check is required for the case when config.w32 doesn't
           bail out on error but silently disables an extension. In this
           case we won't see any bad exit status. */
        $opts = $this->pkg->getConfigureOptions();
        list($ext) = each($opts);
        if (preg_match(',\|\s+'.preg_quote($ext).'\s+\|\s+shared\s+\|,Sm', $this->getlog('configure')) < 1) {
            throw new \Exception("failed to configure the '$ext' extension");
        }
    }

    public function make()
    {
        $backCwd = getcwd();
        chdir($this->tempDir);
        $res = $this->runCommand('nmake');
        chdir($backCwd);

        if (!$res) {
            throw new \Exception('nmake failed');
        }
    }

    public function install()
    {
        $backCwd = getcwd();
        chdir($this->tempDir);

        /* Record the produced DLL filenames. */
        $files = (array) glob("*/*/php_*.dll");
        $files = array_merge($files, glob("*/php_*.dll"));
        $dlls = [];
        foreach ($files as $file) {
            $dlls[] = basename($file);
        }

        $res = $this->runCommand('nmake install');
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('nmake install failed');
        }

        $ini = \Pickle\Engine\Ini::factory(Engine::factory());
        $ini->updatePickleSection($dlls);
    }

    public function getInfo()
    {
        $info = array();
        $info = array_merge($info, $this->getInfoFromPhpizeLog());
        $info = array_merge($info, $this->getInfoFromConfigureLog());

        if (!preg_match(',(.+)/(.+),', $info['name'], $m)) {
            $info['vendor'] = null;
        } else {
            $info['name'] = $m[2];
            $info['vendor'] = $m[1];
        }

        return $info;
    }

    protected function getInfoFromPhpizeLog()
    {
        $ret = array(
            'php_major' => null,
        'php_minor' => null,
        'php_patch' => null,
        );

        $tmp = $this->getLog('phpize');
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

    protected function getInfoFromConfigureLog()
    {
        $info = array(
            'thread_safe' => null,
            'compiler' => null,
            'arch' => null,
            'version' => null,
            'name' => null,
        );

        $tmp = $this->getLog('configure');

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

        $info['version'] = $this->getPackage()->getPrettyVersion();
        $info['name'] = $this->getPackage()->getName();

        return $info;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
