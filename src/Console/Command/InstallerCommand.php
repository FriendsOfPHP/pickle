<?php
namespace Pickle\Console\Command;

use Pickle\Package\JSON\Dumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\Table;
use Pickle\Base\Interfaces\Package;
use Pickle\Engine;
use Pickle\InstallerBinaryWindows;
use Pickle\DependencyLibWindows;

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
            )->addOption(
                'with-configure-options',
                null,
                InputOption::VALUE_REQUIRED,
                'path to the additional configure options'
            )->addOption(
                'save-logs',
                null,
                InputOption::VALUE_REQUIRED,
                'path to save the build logs'
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
        $php = Engine::factory();
        $table = new Table($output);
        $table
            ->setRows([
               ['<info>' . $php->getName() . ' Path</info>', $php->getPath()],
               ['<info>' . $php->getName() . ' Version</info>', $php->getVersion()],
               ['<info>Compiler</info>', $php->getCompiler()],
               ['<info>Architecture</info>', $php->getArchitecture()],
               ['<info>Thread safety</info>', $php->getZts() ? 'yes' : 'no'],
               ['<info>Extension dir</info>', $php->getExtensionDir()],
               ['<info>php.ini</info>', $php->getIniPath()],
            ])
            ->render();

        $inst = new InstallerBinaryWindows($php, $path);
        $progress = $this->getHelperSet()->get('progress');
        $inst->setProgress($progress);
        $inst->setInput($input);
        $inst->setOutput($output);
        $inst->install();

    $deps_handler = new DependencyLibWindows($php);
        $deps_handler->setProgress($progress);
        $deps_handler->setInput($input);
        $deps_handler->setOutput($output);

    $helper = $this->getHelperSet()->get('question');

    $cb = function($choices) use ($helper, $input, $output) {
        $question = new ChoiceQuestion(
            "Multiple choices found, please select the appropriate dependency package",
            $choices
            );
        $question->setMultiselect(false);

        return $helper->ask($input, $output, $question);
    };

    foreach ($inst->getExtDllPaths() as $dll) {
        if (!$deps_handler->resolveForBin($dll, $cb)) {
            throw new \Exception("Failed to resolve dependencies");
        }
    }
    }

    protected function saveSourceInstallLogs(InputInterface $input, $build)
    {
        $save_log_path = $input->getOption('save-logs');
        if ($save_log_path) {
            $build->saveLog($save_log_path);
        }
    }

    protected function sourceInstall($package, InputInterface $input, OutputInterface $output, $optionsValue = [], $force_opts = "")
    {
        $helper = $this->getHelperSet()->get('question');

        $build = \Pickle\Package\Command\Build::factory($package, $optionsValue);

        try {
            $build->prepare();
            $build->phpize();
            $build->createTempDir($package->getName() . $package->getVersion());
            $build->configure($force_opts);
            $build->make();
            $build->install();

            $this->saveSourceInstallLogs($input, $build);
        } catch (\Exception $e) {
            $this->saveSourceInstallLogs($input, $build);

            $output->writeln('The following error(s) happened: ' . $e->getMessage());
            $prompt = new ConfirmationQuestion('Would you like to read the log?', true);
            if ($helper->ask($input, $output, $prompt)) {
                $output->write($build->getLog());
            }
        }
        $build->cleanup();
    }

    protected function buildOptions(Package $package, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelperSet()->get('question');

        $force_opts = $input->getOption('with-configure-options');

        if ($force_opts) {
            if (!file_exists($force_opts) || !is_file($force_opts) || !is_readable($force_opts)) {
                throw new \Exception("File '$force_opts' is unusable");
            }

            $force_opts = preg_replace(",\s+,", " ", file_get_contents($force_opts));

            return [null, $force_opts];
        }

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

        return [$optionsValue, null];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = rtrim($input->getArgument('path'), '/\\');

        /* if windows, try bin install by default */
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $sourceRequested = $input->getOption('source');
            if (!$sourceRequested) {
                $this->binaryInstallWindows($path, $input, $output);

                return 0;
            }
        }

        $package = $this->getHelper("package")->convey($input, $output, $path);

        $this->getHelper('package')->showInfo($output, $package);

        list($optionsValue, $force_opts) = $this->buildOptions($package, $input, $output);

        if ($input->getOption('dry-run')) {
            return 0;
        }

        $this->sourceInstall($package, $input, $output, $optionsValue, $force_opts);

        return 0;
    }
}
