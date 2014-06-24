<?php
namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
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
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Do not install extension'
            );
        ;
    }
	protected function binaryInstallWindows($path, $output)
	{
		$php = new PhpDetection();
        $table = new Table($output);
        $table
            ->setRows([
               ['<info>PHP Path</info>', $php->getPhpCliPath()],
               ['<info>PHP Version</info>', $php->getVersion()],
               ['<info>Compiler</info>', $php->getCompiler()],
               ['<info>Architecture</info>', $php->getArchitecture()],
               ['<info>Extension dir</info>', $php->getExtensionDir()],
            ])
            ->render();

		$inst = new InstallerBinaryWindows($php, $path);
	}

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$path = rtrim($input->getArgument('path'), '/\\');

		/* if windows, try bin install by default */
		if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
			$this->binaryInstallWindows($path, $output);
			return;
		}

        $helper = $this->getHelperSet()->get('question');

        try {
            $pkg = new Package($path);
        } catch (\InvalidArgumentException $exception) {
            if ($input->getOption('no-convert')) {
                throw new \RuntimeException('XML package are not supported. Please convert it before install');
            }

            $this->getApplication()->find('convert')
                ->run(new ArrayInput(['path' => $path]), $output);

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

        if ($input->getOption('dry-run') === false) {
            $build = new BuildSrcUnix($pkg, $options_value);
            $build->phpize();
            $build->createTempDir();
            $build->configure();
            $build->install();
            $build->cleanup();
        }
    }
}
