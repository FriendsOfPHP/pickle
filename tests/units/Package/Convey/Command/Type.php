<?php

/*
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

namespace Pickle\tests\units\Package\Convey\Command;

use atoum;
use Pickle\Base\Pecl\WebsiteFactory;
use Pickle\Package\Convey\Command;

class Type extends atoum
{
    public function test_determine_pickle()
    {
        $this
            ->string(Command\Type::determine('vendor/hello', true))
                ->isIdenticalTo(Command\Type::PICKLE)
        ;
    }

    public function test_determine_pecl()
    {
        $packages = json_decode(file_get_contents(FIXTURES_DIR . '/pecl/packages.json'), true);
        foreach ($packages as $packageName => $packageData) {
            $this
                ->string(Command\Type::determine($packageName, true))
                    ->isIdenticalTo(Command\Type::PECL, "Failed to detect {$packageName} as PECL type")
            ;
            foreach ($packageData['versions'] as $version) {
                $this
                    ->string(Command\Type::determine("{$packageName}-{$version}", true))
                        ->isIdenticalTo(Command\Type::PECL, "Failed to detect {$packageName}-{$version} as PECL type")
                    ->string(Command\Type::determine("{$packageName}@{$version}", true))
                        ->isIdenticalTo(Command\Type::PECL, "Failed to detect {$packageName}@{$version} as PECL type")
                ;
            }
            foreach ($packageData['stabilities'] as $stability) {
                $this
                    ->string(Command\Type::determine("{$packageName}-{$stability}", true))
                        ->isIdenticalTo(Command\Type::PECL, "Failed to detect {$packageName}-{$stability} as PECL type")
                ;
            }
        }
        $this
            ->string(Command\Type::determine('hello', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('hello-stable', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('hello-beta', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('hello-alpha', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('hello-1.2.3', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('hello-1.2', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('hello@1.2.3', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('hello@1.2', true))
                ->isIdenticalTo(Command\Type::PECL)

            ->string(Command\Type::determine('pecl/hello', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('pecl/hello-stable', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('pecl/hello-beta', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('pecl/hello-alpha', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('pecl/hello-1.2.3', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('pecl/hello-1.2', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('pecl/hello@1.2.3', true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine('pecl/hello@1.2', true))
                ->isIdenticalTo(Command\Type::PECL)
        ;
    }

    public function test_determine_git()
    {
        $this
            ->string(Command\Type::determine('https://github.com/weltling/phurple.git', true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine('git@github.com:weltling/phurple.git', true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine('https://github.com/mgdm/Mosquitto-PHP.git', true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine('ssh://user@host.xz:port/path/to/repo.git', true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine('rsync://host.xz/path/to/repo.git', true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine('file:///path/to/repo.git', true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine('file:///path/to/repo.git#somebranch', true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine('file:///path/to/repo.git#some-branch', true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine('file:///path/to/repo.git#some_branch123', true))
                ->isIdenticalTo(Command\Type::GIT)
                ;
    }

    public function test_determine_tgz()
    {
        $this
            ->string(Command\Type::determine('https://github.com/DomBlack/php-scrypt/archive/v1.2.tar.gz', true))
                ->isIdenticalTo(Command\Type::TGZ)
            ->string(Command\Type::determine(WebsiteFactory::getWebsite()->getBaseUrl() . '/get/sync-1.0.1.tgz', true))
                ->isIdenticalTo(Command\Type::TGZ)
            ->string(Command\Type::determine('some_ext-1.2.3a.tgz', false))
                ->isIdenticalTo(Command\Type::TGZ)
        ;
    }

    public function test_determine_srcdir()
    {
        $this
            ->string(Command\Type::determine(getcwd(), false))
                ->isIdenticalTo(Command\Type::SRC_DIR)
            ->string(Command\Type::determine(getcwd(), true))
                ->isIdenticalTo(Command\Type::ANY)
        ;
    }
}
