<?php

/**
 * Pickle
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2015-2015, Pickle community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Pickle\tests\units\Package\PHP;

use atoum;
use mageekguy\atoum\mock\streams\fs;

class Package extends atoum
{
    protected $packageName;
    protected $packageVersion;
    protected $packagePrettyVersion;

    public function beforeTestMethod($method)
    {
        $this->packageName = 'foo bar';
        $this->packageVersion = '1.54.12.5';
        $this->packagePrettyVersion = '1.54.12';

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
                $name = $this->packageName,
                $version = $this->packageVersion,
                $prettyVersion = $this->packagePrettyVersion
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
                $name = $this->packageName,
                $version = $this->packageVersion,
                $prettyVersion = $this->packagePrettyVersion,
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
                $name = $this->packageName,
                $version = $this->packageVersion,
                $prettyVersion = $this->packagePrettyVersion
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
                    'default' => false,
                ],
            ],
            [
                'AC_ARG_ENABLE(foo-bar,[--disable-foo-bar        Disable foo-bar],[ ])',
                'foo-bar',
                [
                    'type' => 'enable',
                    'prompt' => 'Disable foo-bar',
                    'default' => true,
                ],
            ],
            [
                'PHP_ARG_ENABLE(foo-bar,Enable foo-bar,[--enable-foo-bar ])',
                'foo-bar',
                [
                    'type' => 'enable',
                    'prompt' => 'Enable foo-bar',
                    'default' => false,
                ],
            ],
            [
                'PHP_ARG_ENABLE(foo-bar,,[--enable-foo-bar Enable foo-bar])',
                'foo-bar',
                [
                    'type' => 'enable',
                    'prompt' => 'Enable foo-bar',
                    'default' => false,
                ],
            ],
            [
                'PHP_ARG_ENABLE(foo-bar,Disable foo-bar,[--disable-foo-bar        ])',
                'foo-bar',
                [
                    'type' => 'enable',
                    'prompt' => 'Disable foo-bar',
                    'default' => true,
                ],
            ],
            [
                'AC_ARG_WITH(foo-bar,[--with-foo-bar        With foo-bar],[ ])',
                'foo-bar',
                [
                    'type' => 'with',
                    'prompt' => 'With foo-bar',
                    'default' => false,
                ],
            ],
            [
                'AC_ARG_WITH(foo-bar,[--without-foo-bar        Without foo-bar],[ ])',
                'foo-bar',
                [
                    'type' => 'with',
                    'prompt' => 'Without foo-bar',
                    'default' => true,
                ],
            ],
            [
                'PHP_ARG_WITH(foo-bar,With foo-bar,[--with-foo-bar])',
                'foo-bar',
                [
                    'type' => 'with',
                    'prompt' => 'With foo-bar',
                    'default' => false,
                ],
            ],
            [
                'PHP_ARG_WITH(foo-bar,Without foo-bar,[--without-foo-bar        Description])',
                'foo-bar',
                [
                    'type' => 'with',
                    'prompt' => 'Without foo-bar',
                    'default' => true,
                ],
            ],
        ];
    }

    public function testGetConfigureOptionsWindows($config, $optName, $option)
    {
        $this
            ->given(
                $name = $this->packageName,
                $version = $this->packageVersion,
                $prettyVersion = $this->packagePrettyVersion
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
                    'default' => 'no',
                ],
            ],
            [
                "ARG_ENABLE('foo-bar','Disable foo-bar','yes')",
                'foo-bar',
                [
                    'type' => 'enable',
                    'prompt' => 'Disable foo-bar',
                    'default' => 'yes',
                ],
            ],
            [
                "ARG_WITH('foo-bar','With foo-bar','yes')",
                'foo-bar',
                [
                    'type' => 'with',
                    'prompt' => 'With foo-bar',
                    'default' => 'yes',
                ],
            ],
            [
                "ARG_WITH('foo-bar','Without foo-bar','no')",
                'foo-bar',
                [
                    'type' => 'with',
                    'prompt' => 'Without foo-bar',
                    'default' => 'no',
                ],
            ],
        ];
    }

    public function testGetRootDir()
    {
        $this
            ->given(
                $name = $this->packageName,
                $version = $this->packageVersion,
                $prettyVersion = $this->packagePrettyVersion,
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
                $name = $this->packageName,
                $version = $this->packageVersion,
                $prettyVersion = $this->packagePrettyVersion,
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

    public function testGetSourceDirInSubdir()
    {
        $this
            ->given(
                $name = $this->packageName,
                $version = $this->packageVersion,
                $prettyVersion = $this->packagePrettyVersion,
                $packageRoot = FIXTURES_DIR . DIRECTORY_SEPARATOR . "package-subdir-src"
            )
            ->if(
                $this->newTestedInstance($name, $version, $prettyVersion),
                $this->testedInstance->setRootDir((string) $packageRoot)
            )
            ->then
                ->string($this->testedInstance->getSourceDir())->isEqualTo(((string) $packageRoot) . DIRECTORY_SEPARATOR . "ext-source")
        ;
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
        foreach (glob($packageSourceRoot . DIRECTORY_SEPARATOR . "config*.m4") as $f) {
            unlink($f);
        }
        rmdir($packageSourceRoot);
        rmdir($packageRoot);
    }
}
