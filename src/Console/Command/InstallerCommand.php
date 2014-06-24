<?php
namespace Pickle\Console\Command;

use Composer\Config;
use Composer\Downloader\GitDownloader;
use Composer\Downloader\TarDownloader;
use Composer\IO\ConsoleIO;
use Pickle\Downloader\PECLDownloader;
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
        $info = parse_url($path);

        $download = (
            (isset($info['scheme']) && in_array($info['scheme'], ['http', 'https', 'git'])) ||
            (isset($info['scheme']) === false  && is_dir($path) === false)
        );


		/* if windows, try bin install by default */
		if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
			$this->binaryInstallWindows($path, $output);
			return;
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

        $this->getHelper('package')->showInfo($output, $package);

        if (is_dir($path . DIRECTORY_SEPARATOR . $package->getPrettyName() . '-' . $package->getPrettyVersion())) {
            $path .= DIRECTORY_SEPARATOR . $package->getPrettyName() . '-' . $package->getPrettyVersion();
        }

        $package->setRootDir(realpath($path));

        $helper = $this->getHelperSet()->get('question');

        $options = $package->getConfigureOptions();
        $options_value = null;
        if ($options) {
            $options_value = [];

            foreach ($options['enable'] as $name => $opt) {
                /* enable/with-<extname> */
                if ($name == $package->getName()) {
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
            $build = new BuildSrcUnix($package, $options_value);
            $build->phpize();
            $build->createTempDir();
            $build->configure();
            $build->install();
            $build->cleanup();
        }
    }
}
