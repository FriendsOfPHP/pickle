<?php
namespace Pickle\Console\Command;

use Pickle\Package\JSON\Dumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\Table;
use Pickle\Package;
use Pickle\BuildSrcUnix;
use Pickle\PhpDetection;
use Pickle\InstallerBinaryWindows;
use Pickle\BuildSrcWindows;
use Symfony\Component\Console\Question\Question;

class InstallerCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install a php extension')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to the PECL extension root directory (default pwd), archive or extension name',
                getcwd()
            )
            ->addOption(
                'no-convert',
                null,
                InputOption::VALUE_NONE,
                'Disable package conversion'
            )
            ->addOption(
                'defaults',
                null,
                InputOption::VALUE_NONE,
                'Use defaults configure options values'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Do not install extension'
            )->addOption(
                'php',
                null,
                InputArgument::OPTIONAL,
                'path to an alternative php (exec)'
            )->addOption(
                'ini',
                null,
                InputArgument::OPTIONAL,
                'path to an alternative php.ini'
            )->addOption(
                'source',
                null,
                InputOption::VALUE_NONE,
                'use source package'
            );

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->addOption(
                'binary',
                null,
                InputOption::VALUE_NONE,
                'use binary package'
            );
        }
    }

    /**
     * @param string          $path
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function binaryInstallWindows($path, $input, $output)
    {
        $php = new PhpDetection();
        $table = new Table($output);
        $table
            ->setRows([
               ['<info>PHP Path</info>', $php->getPhpCliPath()],
               ['<info>PHP Version</info>', $php->getVersion()],
               ['<info>Compiler</info>', $php->getCompiler()],
               ['<info>Architecture</info>', $php->getArchitecture()],
               ['<info>Thread safety</info>', $php->getZts() ? 'yes' : 'no'],
               ['<info>Extension dir</info>', $php->getExtensionDir()],
               ['<info>php.ini</info>', $php->getPhpIniDir()],
            ])
            ->render();

        $inst = new InstallerBinaryWindows($php, $path);
        $progress = $this->getHelperSet()->get('progress');
        $inst->setProgress($progress);
        $inst->setInput($input);
        $inst->setOutput($output);
        $inst->install();
    }

    /**
     * @param string          $path
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function sourceInstallWindows($path, $input, $output)
    {
        $php = new PhpDetection();
        $php->hasSdk();
        $table = new Table($output);
        $table
            ->setRows([
               ['<info>PHP Path</info>', $php->getPhpCliPath()],
               ['<info>PHP Version</info>', $php->getVersion()],
               ['<info>Compiler</info>', $php->getCompiler()],
               ['<info>Architecture</info>', $php->getArchitecture()],
               ['<info>Thread safety</info>', $php->getZts() ? 'yes' : 'no'],
               ['<info>Extension dir</info>', $php->getExtensionDir()],
               ['<info>php.ini</info>', $php->getPhpIniDir()],
            ])
            ->render();

        $bld = new BuildSrcWindows($php, $path);
        $progress = $this->getHelperSet()->get('progress');
        $bld->setProgress($progress);
        $bld->setInput($input);
        $bld->setOutput($output);
        $bld->phpize();
        $bld->configure();
        $bld->install();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = rtrim($input->getArgument('path'), '/\\');
        $info = parse_url($path);

        $download = (
            (isset($info['scheme']) && in_array($info['scheme'], ['http', 'https', 'git'])) ||
            (false === isset($info['scheme']) && false === is_dir($path))
        );

        /* if windows, try bin install by default */
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $sourceRequested = $input->getOption('source');
            if (!$sourceRequested) {
                $this->binaryInstallWindows($path, $input, $output);

                return 0;
            }
        }

        if ($download) {
            $package = $this->getHelper('package')->download($input, $output, $path, sys_get_temp_dir());

            if (null === $package) {
                throw new \InvalidArgumentException('Package not found: ' . $path);
            }

            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $package->getName();
        }

        $jsonLoader = new Package\JSON\Loader(new Package\Loader());
        $package = null;

        if (file_exists($path . DIRECTORY_SEPARATOR . 'pickle.json')) {
            $package = $jsonLoader->load($path . DIRECTORY_SEPARATOR . 'pickle.json');
        }

        if (null === $package && $input->getOption('no-convert')) {
            throw new \RuntimeException('XML package are not supported. Please convert it before install');
        }

        if (null === $package && file_exists($path . DIRECTORY_SEPARATOR . 'package.xml')) {
            $loader = new Package\XML\Loader(new Package\Loader());
            $package = $loader->load($path . DIRECTORY_SEPARATOR . 'package.xml');

            $dumper = new Dumper();
            $dumper->dumpToFile($package, $path . DIRECTORY_SEPARATOR . 'pickle.json');

            $package = $jsonLoader->load($path . DIRECTORY_SEPARATOR . 'pickle.json');
        }
        $path = realpath($path);
        $package->setRootDir($path);

        $this->getHelper('package')->showInfo($output, $package);
        $helper = $this->getHelperSet()->get('question');

        $options = $package->getConfigureOptions();
        $optionsValue = [];

        foreach ($options as $name => $opt) {
            /* enable/with-<extname> */
            if ($name == $package->getName() || str_replace('-', '_', $name) == $package->getName()) {
                $optionsValue[$name] = (object) [
                    'type' => $opt->type,
                    'input' => true
                ];

                continue;
            }

            if ($input->getOption('defaults')) {
                $value = $opt->default;
            } else {
                if ($opt->type == 'enable') {
                    $prompt = new ConfirmationQuestion($opt->prompt . ' (default: ' . ($opt->default ? 'yes' : 'no') . '): ', $opt->default);
                } else {
                    $prompt = new Question($opt->prompt . ' (default: ' . ($opt->default ? $opt->default : '') . '): ', $opt->default);
                }

                $value = $helper->ask($input, $output, $prompt);
            }

            $optionsValue[$name] = (object) [
                'type' => $opt->type,
                'input' => $value
            ];
        }

        if ($input->getOption('dry-run')) {
            return 0;
        }

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $build = new BuildSrcWindows($package, $optionsValue);
        } else {
            $build = new BuildSrcUnix($package, $optionsValue);
        }
        try {
            $build->phpize();
            $build->createTempDir();
            $build->configure();
            $build->build();
            $build->install();
        } catch (\Exception $e) {
            $output->writeln('The following error(s) happened: ' . $e->getMessage());
            $prompt = new ConfirmationQuestion('Would you like to read the log?', true);
            if ($helper->ask($input, $output, $prompt)) {
                $output->write($build->getLog());
            }
        }
        $build->cleanup();

        return 0;
    }
}
