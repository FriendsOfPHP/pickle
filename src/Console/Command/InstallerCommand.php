<?php
namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use Pickle\Package;
use Pickle\BuildSrcUnix;

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
                'Path to the PECL extension root directory (default pwd), archive or extension name'
            )
            ->addOption(
                'no-convert',
                null,
                InputOption::VALUE_NONE,
                'Disable package conversion'
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelperSet()->get('question');
        $path = rtrim($input->getArgument('path'), '/\\');

        try {
            $pkg = new Package($path);
        } catch (\InvalidArgumentException $exception) {
            if ($input->getOption('no-convert')) {
                throw new \RuntimeException('XML package are not supported. Please convert it before install');
            }

            $this->getApplication()
                ->find('convert')
                ->run($input, $output);

            $pkg = new Package($path);
        }

        $options = $pkg->getConfigureOptions();
        $options_value = null;
        if ($options) {
            $options_value = [];

            foreach ($options['enable'] as $name => $opt) {
                /* enable/with-<extname> */
                if ($name == $pkg->getName()) {
                    $options_value[$name] = true;

                    continue;
                }

                $prompt = new ConfirmationQuestion($opt->prompt . ' (default: ' . ($opt->default ? 'yes' : 'no') . '): ', $opt->default);
                $options_value['enable'][$name] = (object) [
                    'type' => $opt->type,
                    'input' => $helper->ask($input, $output, $prompt)
                ];
            }
        }

        $build = new BuildSrcUnix($pkg, $options_value);
        $build->phpize();
        $build->createTempDir();
        $build->configure();
        $build->install();
        $build->cleanup();

    }
}
