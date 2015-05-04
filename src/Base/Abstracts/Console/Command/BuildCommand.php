<?php

namespace Pickle\Base\Abstracts\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Pickle\Base\Interfaces\Package;

abstract class BuildCommand extends Command
{
    protected function configure()
    {
        $this
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
    }

    protected function saveBuildLogs(InputInterface $input, $build)
    {
        $save_log_path = $input->getOption('save-logs');
        if ($save_log_path) {
            $build->saveLog($save_log_path);
        }
    }

    protected function buildOptions(Package $package, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelperSet()->get('question');

        $force_opts = $input->getOption('with-configure-options');

        if ($force_opts) {
            if (!file_exists($force_opts) || !is_file($force_opts) || !is_readable($force_opts)) {
                throw new \Exception("File '$force_opts' is unusable");
            }

            $force_opts = preg_replace(",\s+,", ' ', file_get_contents($force_opts));

            return [null, $force_opts];
        }

        $options = $package->getConfigureOptions();
        $optionsValue = [];

        foreach ($options as $name => $opt) {
            /* enable/with-<extname> */
            if ($name == $package->getName() || str_replace('-', '_', $name) == $package->getName()) {
                $optionsValue[$name] = (object) [
                'type' => $opt->type,
                'input' => true,
                ];

                continue;
            }

            if ($input->getOption('defaults')) {
                $value = $opt->default;
            } else {
                if ($opt->type == 'enable') {
                    $prompt = new ConfirmationQuestion($opt->prompt.' (default: '.($opt->default ? 'yes' : 'no').'): ', $opt->default);
                } else {
                    $prompt = new Question($opt->prompt.' (default: '.($opt->default ? $opt->default : '').'): ', $opt->default);
                }

                $value = $helper->ask($input, $output, $prompt);
            }

            $optionsValue[$name] = (object) [
                'type' => $opt->type,
                'input' => $value,
            ];
        }

        return [$optionsValue, null];
    }
}
