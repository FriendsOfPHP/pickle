<?php

namespace Pickle\Build\Src;

use Pickle\FileOps;
use Pickle\Package;

abstract class AbstractBuild
{
    use FileOps;

    protected $pkg;
    protected $options;
    protected $log = '';
    protected $cwdBack;
    protected $tempDir;

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
    public function log($level, $msg)
    {
        $this->log .= $level . ': ' . $msg . "\n";
    }

    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param  string     $command
     * @return boolean
     * @throws \Exception
     */
    protected function runCommand($command)
    {
        $this->log(1, 'running: ' . $command);
        $pp = popen("$command 2>&1", 'r');
        if (!$pp) {
            throw new \Exception(
                'Failed to run the following command: ' . $command
            );
        }

        while ($line = fgets($pp, 1024)) {
            $this->log(2, rtrim($line));
        }

        $exitCode = is_resource($pp) ? pclose($pp) : -1;

        return (0 === $exitCode);
    }
}

