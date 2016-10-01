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

namespace Pickle\Base\Abstracts\Package;

use Pickle\Base\Util\FileOps;
use Pickle\Base\Interfaces\Package;
use Exception;
use ZipArchive;

abstract class Build
{
    use FileOps;

    protected $pkg;
    protected $options;
    protected $log = array();
    protected $cwdBack;

    public function __construct(Package $pkg, $options = null)
    {
        $this->pkg = $pkg;
        $this->options = $options;
        $this->cwdBack = getcwd();
    }

    /**
     * @param int    $level
     * @param string $msg
     * @param string $hint
     */
    public function log($level, $msg, $hint = '')
    {
        $this->log[] = [
            'level' => $level,
            'msg' => $msg,
            'hint' => $hint,
        ];
    }

    public function getLog($hint = null)
    {
        $ret = array();

        if (!$this->log) {
            return null;
        }

        foreach ($this->log as $item) {
            if (isset($hint) && $hint !== $item['hint']) {
                continue;
            }

            $tmp = explode("\n", $item['msg']);
            foreach ($tmp as $ln) {
                $ret[] = $item['level'].': '.$ln;
            }
        }

        return implode("\n", $ret);
    }

    protected function prepareSaveLog($path, &$defFl)
    {
        if ($path && !is_dir($path)) {
            if (!mkdir($path)) {
                throw new Exception("Location '$path' could not be created, unable to save build logs");
            }
        }

        $defFl = $path.DIRECTORY_SEPARATOR.'build.log';
        if (file_exists($defFl)) {
            unlink($defFl);
        }
    }

    protected function getLogFilename($path, $logItem, $defFl, array &$logs)
    {
        $isHint = (isset($logItem['hint']) && !empty($logItem['hint']));
        $fname = $isHint ? $path.DIRECTORY_SEPARATOR."$logItem[hint].log" : $defFl;

        if (!in_array($fname, $logs)) {
            if (file_exists($fname)) {
                unlink($fname);
            }

            $logs[] = $fname;
        }

        return $fname;
    }

    public function saveLog($path)
    {
        $logs = array();
        $defFl = null;

        $this->prepareSaveLog($path, $defFl);

        foreach ($this->log as $item) {
            $fname = $this->getLogFilename($path, $item, $defFl, $logs);

            if (file_put_contents($fname, "$item[msg]\n", FILE_APPEND) != strlen($item['msg']) + 1) {
                throw new Exception("Couldn't write contents to '$fname'");
            }
        }
    }

    protected function fixEol($string)
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            return preg_replace(",(?!\r)\n,", "\r\n", $string);
        }

        return $string;
    }

    /* zip is default */
    public function packLog($path)
    {
        $zip = new ZipArchive();
        if (!$zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new Exception("Failed to open '$path' for writing");
        }

        $noHintLogs = '';
        foreach ($this->log as $item) {
            $msg = $this->fixEol($item['msg']);
            if (!empty($item['hint'])) {
                $zip->addFromString("$item[hint].log", $msg);
            } else {
                $noHintLogs = "$noHintLogs\n\n$msg";
            }
        }

        if ($noHintLogs) {
            $zip->addFromString('build.log', $this->fixEol($item['msg']));
        }

        $zip->close();
    }

    /**
     * @param string $command
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function runCommand($command)
    {
        $hint = basename(strtok($command, " \n"));

        $this->log(1, $command, $hint);
        $pp = popen("$command 2>&1", 'r');
        if (!$pp) {
            throw new Exception(
                'Failed to run the following command: '.$command
            );
        }

        $out = array();
        while ($line = fgets($pp, 1024)) {
            $out[] = rtrim($line);
        }

        $this->log(2, implode("\n", $out), $hint);

        $exitCode = is_resource($pp) ? pclose($pp) : -1;

        return 0 === $exitCode;
    }

    protected function appendPkgConfigureOptions(&$configureOptions)
    {
        $opt = $this->pkg->getConfigureOptions();
        if (isset($opt[$this->pkg->getName()])) {
            $extEnableOption = $opt[$this->pkg->getName()];
            if ('enable' == $extEnableOption->type) {
                $confOption = '--enable-'.$this->pkg->getName().'=shared';
            } else {
                $confOption = '--with-'.$this->pkg->getName().'=shared';
            }
            $configureOptions = $confOption.' '.$configureOptions;
        } else {
            $name = str_replace('_', '-', $this->pkg->getName());
            if (isset($opt[$name])) {
                $extEnableOption = $opt[$name];
                if ('enable' == $extEnableOption->type) {
                    $confOption = '--enable-'.$name.'=shared';
                } else {
                    $confOption = '--with-'.$name.'=shared';
                }
                $configureOptions = $confOption.' '.$configureOptions;
            }
        }
    }

    public function getPackage()
    {
        return $this->pkg;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
