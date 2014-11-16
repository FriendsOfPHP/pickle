<?php
namespace Pickle\Build\Src;

use Pickle\Build\Src\Build;
use Pickle\Build\Src\AbstractBuild;

class Windows extends AbstractBuild implements Build
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

    protected function prepareConfigOpts()
    {
        $configureOptions = '';
        foreach ($this->options as $name => $option) {
            $decision = null;
            if ('enable' === $option->type) {
                $decision = true == $option->input ? 'enable' : 'disable';
            } elseif ('disable' == $option->type) {
                $decision = false == $option->input ? 'enable' : 'disable';
            }

            if (!is_null($decision)) {
                $configureOptions .= ' --' . $decision . '-' . $name;
            }
        }

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

        $res = $this->runCommand($this->pkg->getSourceDir() . '/configure '. $configureOptions);
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('configure failed, see log at '. $this->tempDir . '\config.log');
        }

        /* This post check is required for the case when config.w32 doesn't
           bail out on error but silently disables an extension. In this
           case we won't see any bad exit status. */
        $opts = $this->pkg->getConfigureOptions();
        list($ext) = each($opts);
        if (preg_match(',\|\s+' . preg_quote($ext) . '\s+\|\s+shared\s+\|,Sm', $this->getlog("configure")) < 1) {
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
        $res = $this->runCommand('nmake install');
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('nmake install failed');
        }
    }
}
