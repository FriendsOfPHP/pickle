<?php
namespace Pickle;

class BuildSrcUnix extends BuildSrc
{
    public function prepare()
    {

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

    public function configure($opts = NULL)
    {
        $backCwd = getcwd();
        chdir($this->tempDir);
        if ($opts) {
            $configureOptions = $opts;
        } else {
            $configureOptions = '';
            foreach ($this->options as $name => $option) {
                if ('enable' === $option->type) {
                    true == $option->input ? 'enable' : 'disable';
                } elseif ('disable' == $option->type) {
                    false == $option->input ? 'enable' : 'disable';
                } elseif ('with' === $option->type) {
                    if ($option->input == 'yes' || $option->input == '1' || $option->type === true) {
                        $configureOptions .= ' --with-' . $name;
                    } elseif ($option->input == 'no' || $option->input == '0' || $option->type === false) {
                        $configureOptions .= ' --without-' . $name;
                    } else {
                         $configureOptions .= ' --with-' . $name. '=' . $option->input;
                    }
                }
            }
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
}

