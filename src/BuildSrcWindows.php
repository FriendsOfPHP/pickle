<?php
namespace Pickle;

class BuildSrcWindows extends BuildSrc
{
    public function prepare()
    {
    	if (!file_exists("c:\\php-sdk\\bin")) {
		throw new \Exception("PHP SDK not found");
	}
        putenv("path=c:\\php-sdk\\bin;" . getenv("path"));

	if (!$this->runCommand("phpsdk_setvars")) {
		throw new \Exception("phpsdk_setvars failed");
	}
    }

    /**
     * @param string $src
     */
    private function copySrcDir($src, $dest)
    {
        foreach (scandir($src) as $file) {
            $srcfile = rtrim($src, '/') .'/'. $file;
            $destfile = rtrim($dest, '/') .'/'. $file;
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

    public function configure()
    {
        /* duplicate src tree to do not pollute repo or src dir */
        $this->copySrcDir($this->pkg->getSourceDir(), $this->tempDir);
        $backCwd = getcwd();
        chdir($this->tempDir);
        $configureOptions = '';
	var_dump($this->options);
        foreach ($this->options as $name => $option) {
            $decision = NULL;
            if ('enable' === $option->type) {
                $decision = true == $option->input ? 'enable' : 'disable';
            } elseif ('disable' == $option->type) {
                $decision = false == $option->input ? 'enable' : 'disable';
	    }

            if (!is_null($decision)) {
                $configureOptions .= ' --' . $decision . '-' . $name;
	    }
        }

        $extEnableOption = $this->options[$this->pkg->getName()];
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
        $res = $this->runCommand('nmake install');
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('nmake install failed');
        }
    }
}

