<?php
namespace Pickle;

class BuildSrcUnix
{
    use FileOps;

    private $pkg;
    private $options;
    private $log = '';
    private $cwdBack;
    private $tempDir;

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

    public function configure()
    {
        $backCwd = getcwd();
        chdir($this->tempDir);
        $configureOptions = '';
        foreach ($this->options as $name => $option) {
            if ('enable' === $option->type) {
                $decision = true == $option->input ? 'enable' : 'disable';
            } elseif ('disable' == $option->type) {
                $decision = false == $option->input ? 'enable' : 'disable';
            } else {
                throw new \Exception(
                    'Option ' . $name . ' is not well-formed; ' .
                    'its type must be “enable” or “disable”, got ' .
                    $option->type
                );
            }

            $configureOptions .= ' --' . $decision . '-' . $name;
        }
        $opt = $this->pkg->getConfigureOptions();
        $extEnableOption = $opt[$this->pkg->getName()];
        if ('enable' == $extEnableOption->type) {
            $confOption = '--enable-' . $this->pkg->getName() . '=shared';
        } else {
            $confOption = '--with-' . $this->pkg->getName() . '=shared';
        }
        $configureOptions = $confOption . ' ' . $configureOptions;

        $res = $this->runCommand($this->pkg->getSourceDir() . '/configure '. $configureOptions);
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('configure failed, see log at '. $this->tempDir . '\config.log');
        }
    }

    public function build()
    {
        $backCwd = getcwd();
        chdir($this->tempDir);
        $res = $this->runCommand('make');
        chdir($backCwd);

        if (!$res) {
            throw new \Exception('make failed');
        }
    }

    public function install()
    {
        $backCwd = getcwd();
        chdir($this->tempDir);
        $res = $this->runCommand('make install');
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('make install failed');
        }
    }

    /**
     * @param string $command
     * @return boolean
     * @throws \Exception
     */
    private function runCommand($command)
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

    public function getLog()
    {
        return $this->log;
    }
}
