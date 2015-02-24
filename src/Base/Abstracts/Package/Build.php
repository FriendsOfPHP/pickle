<?php

namespace Pickle\Base\Abstracts\Package;

use Pickle\Base\Util\FileOps;
use Pickle\Base\Interfaces\Package;

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
     * @param integer $level
     * @param string  $msg
     */
    public function log($level, $msg, $hint = '')
    {
        $this->log[] = [
            "level" => $level,
            "msg" => $msg,
            "hint" => $hint,
        ];
    }

    public function getLog($hint = null)
    {
        $ret = array();

        foreach ($this->log as $item) {
            if (isset($hint) && $hint !== $item["hint"]) {
                continue;
            }
            $tmp = explode("\n", $item["msg"]);
            foreach ($tmp as $ln) {
                $ret[] =  $item["level"] . ": " . $ln;
            }
        }

        return implode("\n", $ret);
    }

    protected function prepareSaveLog($path, &$def_fl)
    {
        if ($path && !is_dir($path)) {
            if (!mkdir($path)) {
                throw new \EXception("Location '$path' could not be created, unable to save build logs");
            }
        }

        $def_fl = $path . DIRECTORY_SEPARATOR . "build.log";
        if (file_exists($def_fl)) {
            unlink($def_fl);
        }
    }

    protected function getLogFilename($path, $log_item, $def_fl, array &$logs)
    {
        $is_hint = (isset($log_item["hint"]) && !empty($log_item["hint"]));
        $fname = $is_hint ? $path . DIRECTORY_SEPARATOR . "$log_item[hint].log" : $def_fl;

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
        $def_fl = null;

        $this->prepareSaveLog($path, $def_fl);

        foreach ($this->log as $item) {
            $fname = $this->getLogFilename($path, $item, $def_fl, $logs);

            if (file_put_contents($fname, "$item[msg]\n", FILE_APPEND) != strlen($item["msg"])+1) {
                throw new \Exception("Couldn't write contents to '$fname'");
            }
        }
    }

    /**
     * @param  string     $command
     * @return boolean
     * @throws \Exception
     */
    protected function runCommand($command)
    {
        $hint = basename(strtok($command, " \n"));

        $this->log(1, $command, $hint);
        $pp = popen("$command 2>&1", 'r');
        if (!$pp) {
            throw new \Exception(
                'Failed to run the following command: ' . $command
            );
        }

        $out = array();
        while ($line = fgets($pp, 1024)) {
            $out[] = rtrim($line);
        }
        $this->log(2, implode("\n", $out), $hint);

        $exitCode = is_resource($pp) ? pclose($pp) : -1;

        return (0 === $exitCode);
    }

    protected function appendPkgConfigureOptions(&$configureOptions)
    {
        $opt = $this->pkg->getConfigureOptions();
        if (isset($opt[$this->pkg->getName()])) {
            $extEnableOption = $opt[$this->pkg->getName()];
            if ('enable' == $extEnableOption->type) {
                $confOption = '--enable-' . $this->pkg->getName() . '=shared';
            } else {
                $confOption = '--with-' . $this->pkg->getName() . '=shared';
            }
            $configureOptions = $confOption . ' ' . $configureOptions;
        } else {
            $name = str_replace('_', '-', $this->pkg->getName());
            if (isset($opt[$name])) {
                $extEnableOption = $opt[$name];
                if ('enable' == $extEnableOption->type) {
                    $confOption = '--enable-' . $name . '=shared';
                } else {
                    $confOption = '--with-' . $name . '=shared';
                }
                $configureOptions = $confOption . ' ' . $configureOptions;
            }
        }
    }
}
