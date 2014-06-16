<?php
namespace Pickle;

class BuildSrcUnix
{
    private $pkg;
    private $options;
    private $cwd_bak;
    private $log = '';
    private $build_dir;

    public function __construct($pkg, $options = NULL)
    {
        $this->pkg = $pkg;
        $this->options = $options;
        $this->cwd_back = getcwd();
    }

    public function log($level, $msg)
    {
        $this->log .= $level . ': ' . $msg . "\n";
    }

    public function createTempDir()
    {
        $tmp = sys_get_temp_dir();
        $build_dir = $tmp . '/pickle-' . $this->pkg->getName();
        var_dump($build_dir);
        mkdir($build_dir);
        $this->build_dir = $build_dir;
    }

    public function cleanup()
    {
        if (is_dir($this->build_dir)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->build_dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
                $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
            }
            rmdir($this->build_dir);
        }

        function phpize()
        {
            chdir($this->pkg->getRootDir());
            $this->_runCommand('phpize');
        }

        function configure()
        {
            chdir($this->build_dir);
            var_dump(getcwd());
            $configure_options = '';
            var_dump($this->options['enable']);
            foreach ($this->options['enable'] as $n => $opt) {
                if ($opt->type == 'enable') {
                    $t = $opt->input == true ? 'enable' : 'disable';
                } elseif ($opt->type == 'disable') {
                    $t = $opt->input == false ? 'enable' : 'disable';
                }

                $configure_options .= ' --' . $t . '-' . $n;
            }
            $enable_ext = '';
            $opt = $this->pkg->getConfigureOptions();
            $ext_enable_option = $opt['enable'][$this->pkg->getName()];
            var_dump($ext_enable_option);
            if ($ext_enable_option->type == 'enable') {
                $conf_option = '--enable-' . $this->pkg->getName() . '=shared';
            } else {
                $conf_option = '--with-' . $this->pkg->getName() . '=shared';
            }
            $configure_options = $conf_option . ' ' . $configure_options;
            var_dump($configure_options);
            var_dump($this->pkg->getRootDir() . '/configure '. $configure_options);
            $this->_runCommand($this->pkg->getRootDir() . '/configure '. $configure_options);
        }

        function build()
        {
            $configure_options = '';

            foreach ($this->options['enable'] as $n => $opt) {
                if ($opt->type == 'enable') {
                    $t = $opt->input == true ? 'enable' : 'disable';
                } elseif ($opt->type == 'disable') {
                    $t = $opt->input == false ? 'enable' : 'disable';
                }

                $configure_options .= ' --' . $t . '-' . $n;
            }
            //$configure_options .= ' --' . $n . '=' . escapeshellarg(trim($opt));
            echo "$configure_options\n";
            /*
               if (is_object($descfile)) {
               $pkg = $descfile;
               $descfile = $pkg->getPackageFile();
               if (is_a($pkg, 'PEAR_PackageFile_v1')) {
               $dir = dirname($descfile);
               } else {
               $dir = $pkg->_config->get('temp_dir') . '/' . $pkg->getName();
            // automatically delete at session end
            $this->addTempFile($dir);
            }
            } else {
            $pf = &new PEAR_PackageFile($this->config);
            $pkg = &$pf->fromPackageFile($descfile, PEAR_VALIDATE_NORMAL);
            if (PEAR::isError($pkg)) {
            return $pkg;
            }
            $dir = dirname($descfile);
            }

            // Find config. outside of normal path - e.g. config.m4
            foreach (array_keys($pkg->getInstallationFileList()) as $item) {
            if (stristr(basename($item), 'config.m4') && dirname($item) != '.') {
            $dir .= DIRECTORY_SEPARATOR . dirname($item);
            break;
            }
            }

            $old_cwd = getcwd();
            if (!file_exists($dir) || !is_dir($dir) || !chdir($dir)) {
            return $this->raiseError("could not chdir to $dir");
            }

            $vdir = $pkg->getPackage() . '-' . $pkg->getVersion();
            if (is_dir($vdir)) {
            chdir($vdir);
            }

            $dir = getcwd();
            $this->log(2, "building in $dir");
            putenv('PATH=' . $this->config->get('bin_dir') . ':' . getenv('PATH'));
            $err = $this->_runCommand($this->config->get('php_prefix')
            . "phpize" .
            $this->config->get('php_suffix'),
            array(&$this, 'phpizeCallback'));
            if (PEAR::isError($err)) {
            return $err;
            }

            if (!$err) {
            return $this->raiseError("`phpize' failed");
            }

            // Figure out what params have been passed in to us already - formatting fixing
            $opts = array();
            if (!empty($options)) {
            foreach ($options as $op) {
            $op = str_replace('--', '', $op);
            list($name, $value) = explode('=', $op);
            $opts[] = $name;
            }
            }

            // {{{ start of interactive part
            $configure_command = "$dir/configure";
            $configure_options = $pkg->getConfigureOptions();
            if ($configure_options) {
            foreach ($configure_options as $o) {
            // skip params that have been passed already
            if (in_array($o['name'], $opts)) {
            continue;
        }

        $default = array_key_exists('default', $o) ? $o['default'] : null;
        list($r) = $this->ui->userDialog('build',
                array($o['prompt']),
                array('text'),
                array($default));
        if (substr($o['name'], 0, 5) == 'with-' &&
                ($r == 'yes' || $r == 'autodetect')) {
            $configure_command .= " --$o[name]";
        } else {
            $configure_command .= " --$o[name]=".trim($r);
        }
        }
        }
        // }}} end of interactive part

        // Set any options that were passed in.
        if (!empty($options)) {
            foreach ($options as $op) {
                $configure_command .= ' ' . $op;
            }
        }

        // FIXME make configurable
        if (!$user = getenv('USER')) {
            $user = 'defaultuser';
        }

        $tmpdir = $this->config->get('temp_dir');
        $build_basedir = System::mktemp(' -t "' . $tmpdir . '" -d "pear-build-' . $user . '"');
        $build_dir = "$build_basedir/$vdir";
        $inst_dir = "$build_basedir/install-$vdir";
        $this->log(1, "building in $build_dir");
        if (is_dir($build_dir)) {
            System::rm(array('-rf', $build_dir));
        }

        if (!System::mkDir(array('-p', $build_dir))) {
            return $this->raiseError("could not create build dir: $build_dir");
        }

        $this->addTempFile($build_dir);
        if (!System::mkDir(array('-p', $inst_dir))) {
            return $this->raiseError("could not create temporary install dir: $inst_dir");
        }
        $this->addTempFile($inst_dir);

        $make_command = getenv('MAKE') ? getenv('MAKE') : 'make';

        $to_run = array(
                $configure_command,
                $make_command,
                "$make_command INSTALL_ROOT=\"$inst_dir\" install",
                "find \"$inst_dir\" | xargs ls -dils"
                );
        if (!file_exists($build_dir) || !is_dir($build_dir) || !chdir($build_dir)) {
            return $this->raiseError("could not chdir to $build_dir");
        }

        putenv('PHP_PEAR_VERSION=@PEAR-VER@');
        foreach ($to_run as $cmd) {
            $err = $this->_runCommand($cmd, $callback);
            if (PEAR::isError($err)) {
                chdir($old_cwd);

                return $err;
            }

            if (!$err) {
                chdir($old_cwd);

                return $this->raiseError("`$cmd' failed");
            }
        }

        if (!($dp = opendir("modules"))) {
            chdir($old_cwd);

            return $this->raiseError("no `modules' directory found");
        }

        $built_files = array();
        $prefix = exec($this->config->get('php_prefix')
                . "php-config" .
                $this->config->get('php_suffix') . " --prefix");
        $ext_dir = $this->config->get('ext_dir');
        if (!$ext_dir) {
            $ext_dir = $prefix;
        }
        $this->_harvestInstDir($ext_dir, $inst_dir . DIRECTORY_SEPARATOR . $prefix, $built_files);

        chdir($old_cwd);

        return $built_files;
        */
        }

        function _runCommand($command, $callback = null)
        {
            $this->log(1, "running: $command");
            $pp = popen("$command 2>&1", "r");
            if (!$pp) {
                return $this->raiseError("failed to run `$command'");
            }

            if ($callback && $callback[0]->debug == 1) {
                $olddbg = $callback[0]->debug;
                $callback[0]->debug = 2;
            }

            while ($line = fgets($pp, 1024)) {
                if ($callback) {
                    call_user_func($callback, 'cmdoutput', $line);
                } else {
                    $this->log(2, rtrim($line));
                }
            }

            if ($callback && isset($olddbg)) {
                $callback[0]->debug = $olddbg;
            }

            $exitcode = is_resource($pp) ? pclose($pp) : -1;

            return ($exitcode == 0);
        }

    }
