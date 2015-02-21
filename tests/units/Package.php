<?php
namespace Pickle\tests\units;

use atoum;
use mageekguy\atoum\mock\streams\fs;
use mageekguy\atoum\test\adapter\call;
use Pickle\tests;

class Package extends atoum
{
    protected $packageName;
    protected $packageVersion;
    protected $packagePrettyVersion;

    public function beforeTestMethod($method)
    {
        $this->packageName = $this->realdom->regex('/\w+/');
        $this->packageVersion = $this->realdom->regex('/\d+(\.\d+){3}/');
        $this->packagePrettyVersion = $this->realdom->regex('/\d+(\.\d+){2}/');

        $windows = defined('PHP_WINDOWS_VERSION_MAJOR');

        if ($method === 'testGetConfigureOptionsWindows' && $windows === false) {
            define('PHP_WINDOWS_VERSION_MAJOR', uniqid());
        }

        if ($method === 'testGetConfigureOptions' && $windows) {
            $this->skip('Cannot run on Windows');
        }
    }

    public function test__construct()
    {
        $this
            ->given(
                $name = $this->sample($this->packageName),
                $version = $this->sample($this->packageVersion),
                $prettyVersion = $this->sample($this->packagePrettyVersion)
            )
            ->if($this->newTestedInstance($name, $version, $prettyVersion))
            ->then
                ->string($this->testedInstance->getPrettyName())->isEqualTo($name)
                ->string($this->testedInstance->getPrettyVersion())->isEqualTo($prettyVersion)
                ->string($this->testedInstance->getVersion())->isEqualTo($version)
                ->string($this->testedInstance->getStability())->isEqualTo('stable')
                ->boolean($this->testedInstance->isDev())->isFalse
        ;
    }

    public function testGetConfigureOptionsEmptyFile()
    {
        $this
            ->given(
                $name = $this->sample($this->packageName),
                $version = $this->sample($this->packageVersion),
                $prettyVersion = $this->sample($this->packagePrettyVersion),
                $packageRoot = FIXTURES_DIR . DIRECTORY_SEPARATOR . "package"
            )
            ->if(
                $this->newTestedInstance($name, $version, $prettyVersion),
                $this->testedInstance->setRootDir($packageRoot)
            )
            ->then
                ->array($this->testedInstance->getConfigureOptions())->isEmpty()
        ;
    }

    public function testGetConfigureOptions($config, $optName, $option)
    {
        $this
            ->given(
                $name = $this->sample($this->packageName),
                $version = $this->sample($this->packageVersion),
                $prettyVersion = $this->sample($this->packagePrettyVersion)
            )
            ->if(
                $this->newTestedInstance($name, $version, $prettyVersion),
		list($packageRoot, $packageSourceRoot) = $this->createTmpPackageStruct(),
                $configM4 = $packageSourceRoot . DIRECTORY_SEPARATOR . 'config.m4',
                file_put_contents($configM4, $config),
                $this->testedInstance->setRootDir((string) $packageRoot)
            )
            ->then
                ->array($this->testedInstance->getConfigureOptionsFromFile((string) $configM4))
                    ->object[$optName]->isEqualTo((object) $option)
        ;

	$this->removeTmpPackageStruct($packageRoot, $packageSourceRoot);
    }

    protected function testGetConfigureOptionsDataProvider()
    {
        return [
            [
                'AC_ARG_ENABLE(foo-bar,[--enable-foo-bar        Enable foo-bar],[ ])',
                'foo-bar',
                [
                    'type' => 'enable',
                    'prompt' => 'Enable foo-bar',
                    'default' => false
                ],
            ],
            [
                'AC_ARG_ENABLE(foo-bar,[--disable-foo-bar        Disable foo-bar],[ ])',
                'foo-bar',
                [
                    'type' => 'enable',
                    'prompt' => 'Disable foo-bar',
                    'default' => true
                ]
            ],
            [
                'PHP_ARG_ENABLE(foo-bar,Enable foo-bar,[--enable-foo-bar ])',
                'foo-bar',
                [
                    'type' => 'enable',
                    'prompt' => 'Enable foo-bar',
                    'default' => false
                ]
            ],
            [
                'PHP_ARG_ENABLE(foo-bar,,[--enable-foo-bar Enable foo-bar])',
                'foo-bar',
                [
                    'type' => 'enable',
                    'prompt' => 'Enable foo-bar',
                    'default' => false
                ]
            ],
            [
                'PHP_ARG_ENABLE(foo-bar,Disable foo-bar,[--disable-foo-bar        ])',
                'foo-bar',
                [
                    'type' => 'enable',
                    'prompt' => 'Disable foo-bar',
                    'default' => true
                ]
            ],
            [
                'AC_ARG_WITH(foo-bar,[--with-foo-bar        With foo-bar],[ ])',
                'foo-bar',
                [
                    'type' => 'with',
                    'prompt' => 'With foo-bar',
                    'default' => false
                ]
            ],
            [
                'AC_ARG_WITH(foo-bar,[--without-foo-bar        Without foo-bar],[ ])',
                'foo-bar',
                [
                    'type' => 'with',
                    'prompt' => 'Without foo-bar',
                    'default' => true
                ]
            ],
            [
                'PHP_ARG_WITH(foo-bar,With foo-bar,[--with-foo-bar])',
                'foo-bar',
                [
                    'type' => 'with',
                    'prompt' => 'With foo-bar',
                    'default' => false
                ]
            ],
            [
                'PHP_ARG_WITH(foo-bar,Without foo-bar,[--without-foo-bar        Description])',
                'foo-bar',
                [
                    'type' => 'with',
                    'prompt' => 'Without foo-bar',
                    'default' => true
                ]
            ]
        ];
    }

    public function testGetConfigureOptionsWindows($config, $optName, $option)
    {
        $this
            ->given(
                $name = $this->sample($this->packageName),
                $version = $this->sample($this->packageVersion),
                $prettyVersion = $this->sample($this->packagePrettyVersion)
            )
            ->if(
                $this->newTestedInstance($name, $version, $prettyVersion),
		list($packageRoot, $packageSourceRoot) = $this->createTmpPackageStruct(),
                $configW32 = $packageSourceRoot . DIRECTORY_SEPARATOR . 'config.w32',
                file_put_contents($configW32, $config),
                $this->testedInstance->setRootDir((string) $packageRoot)
            )
            ->then
                ->array($this->testedInstance->getConfigureOptions())
                    ->object[$optName]->isEqualTo((object) $option)
        ;

	$this->removeTmpPackageStruct($packageRoot, $packageSourceRoot);
    }

    protected function testGetConfigureOptionsWindowsDataProvider()
    {
        return [
            [
                "ARG_ENABLE('foo-bar','Enable foo-bar','no')",
                'foo-bar',
                [
                    'type' => 'enable',
                    'prompt' => 'Enable foo-bar',
                    'default' => 'no'
                ],
            ],
            [
                "ARG_ENABLE('foo-bar','Disable foo-bar','yes')",
                'foo-bar',
                [
                    'type' => 'enable',
                    'prompt' => 'Disable foo-bar',
                    'default' => 'yes'
                ]
            ],
            [
                "ARG_WITH('foo-bar','With foo-bar','yes')",
                'foo-bar',
                [
                    'type' => 'with',
                    'prompt' => 'With foo-bar',
                    'default' => 'yes'
                ]
            ],
            [
                "ARG_WITH('foo-bar','Without foo-bar','no')",
                'foo-bar',
                [
                    'type' => 'with',
                    'prompt' => 'Without foo-bar',
                    'default' => 'no'
                ]
            ]
        ];
    }

    public function testGetRootDir()
    {
        $this
            ->given(
                $name = $this->sample($this->packageName),
                $version = $this->sample($this->packageVersion),
                $prettyVersion = $this->sample($this->packagePrettyVersion),
                $packageRoot = fs\directory::get()
            )
            ->if(
                $this->newTestedInstance($name, $version, $prettyVersion),
                $this->testedInstance->setRootDir((string) $packageRoot)
            )
            ->then
                ->string($this->testedInstance->getRootDir())->isEqualTo((string) $packageRoot)
            ->if(
                clearstatcache(),
                $packageSourceRoot = fs\directory::getSubStream($packageRoot, $this->testedInstance->getPrettyName() . '-' . $this->testedInstance->getPrettyVersion()),
                $packageSourceRoot->url_stat = ['mode' => 17000] // Be a directory
            )
            ->then
                ->string($this->testedInstance->getRootDir())->isEqualTo((string) $packageRoot)
        ;
    }

    public function testGetSourceDir()
    {
        $this
            ->given(
                $name = $this->sample($this->packageName),
                $version = $this->sample($this->packageVersion),
                $prettyVersion = $this->sample($this->packagePrettyVersion),
                $packageRoot = FIXTURES_DIR . DIRECTORY_SEPARATOR . "package"
            )
            ->if(
                $this->newTestedInstance($name, $version, $prettyVersion),
                $this->testedInstance->setRootDir((string) $packageRoot)
            )
            ->then
                ->string($this->testedInstance->getSourceDir())->isEqualTo((string) $packageRoot)
            ->if(
                clearstatcache(),
		list($packageRoot, $packageSourceRoot) = $this->createTmpPackageStruct(),
                $this->testedInstance->setRootDir((string) $packageRoot)
            )
            ->then
                ->string($this->testedInstance->getSourceDir())->isEqualTo((string) $packageSourceRoot)
        ;

	$this->removeTmpPackageStruct($packageRoot, $packageSourceRoot);

    }

    protected function createTmpPackageStruct()
    {
	$packageRoot = FIXTURES_DIR . DIRECTORY_SEPARATOR . "package-" . md5(uniqid());
	$packageSourceRoot = $packageRoot . DIRECTORY_SEPARATOR . $this->testedInstance->getPrettyName() . '-' . $this->testedInstance->getPrettyVersion();
	mkdir($packageRoot);
	mkdir($packageSourceRoot);
	file_put_contents($packageSourceRoot . DIRECTORY_SEPARATOR . "config.w32", "");
	file_put_contents($packageSourceRoot . DIRECTORY_SEPARATOR . "config0.m4", "");

	return array($packageRoot, $packageSourceRoot);
    }

    protected function removeTmpPackageStruct($packageRoot, $packageSourceRoot)
    {
	if (!$packageSourceRoot) {
		$packageSourceRoot = $packageRoot;
	}

	unlink($packageSourceRoot . DIRECTORY_SEPARATOR . "config.w32");
	unlink($packageSourceRoot . DIRECTORY_SEPARATOR . "config0.m4");
	rmdir($packageSourceRoot);
	rmdir($packageRoot);
    }
}
