<?php

namespace Pickle\tests\units\Package\Convey\Command;

use atoum;
use Pickle\tests;
use Pickle\Package\Convey\Command;

class Type extends atoum
{
    public function test_determine_any()
    {
        $this
            ->string(Command\Type::determine("hello", false))
                ->isIdenticalTo(Command\Type::ANY);
    }

    public function test_determine_pecl()
    {
        $this
            ->string(Command\Type::determine("hello", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("hello-stable", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("hello-beta", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("hello-alpha", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("hello-1.2.3", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("hello-1.2", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("hello@1.2.3", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("hello@1.2", true))
                ->isIdenticalTo(Command\Type::PECL)

            ->string(Command\Type::determine("pecl/hello", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("pecl/hello-stable", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("pecl/hello-beta", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("pecl/hello-alpha", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("pecl/hello-1.2.3", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("pecl/hello-1.2", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("pecl/hello@1.2.3", true))
                ->isIdenticalTo(Command\Type::PECL)
            ->string(Command\Type::determine("pecl/hello@1.2", true))
                ->isIdenticalTo(Command\Type::PECL)
                ;

            /* XXX fix version tests */
    }

    public function test_determine_git()
    {
        $this
            ->string(Command\Type::determine("https://github.com/weltling/phurple.git", true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine("git@github.com:weltling/phurple.git", true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine("https://github.com/mgdm/Mosquitto-PHP.git", true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine("ssh://user@host.xz:port/path/to/repo.git", true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine("rsync://host.xz/path/to/repo.git", true))
                ->isIdenticalTo(Command\Type::GIT)
            ->string(Command\Type::determine("file:///path/to/repo.git", true))
                ->isIdenticalTo(Command\Type::GIT)
                ;

    }

    public function test_determine_tgz()
    {
        $this
            ->string(Command\Type::determine("https://github.com/DomBlack/php-scrypt/archive/v1.2.tar.gz", true))
                ->isIdenticalTo(Command\Type::TGZ)
            ->string(Command\Type::determine("http://pecl.php.net/get/sync-1.0.1.tgz", true))
                ->isIdenticalTo(Command\Type::TGZ)
            ->string(Command\Type::determine("some_ext-1.2.3a.tgz", false))
                ->isIdenticalTo(Command\Type::TGZ);
    }

    public function test_determine_srcdir()
    {
        $this
            ->string(Command\Type::determine(getcwd(), false))
                ->isIdenticalTo(Command\Type::SRC_DIR)
            ->string(Command\Type::determine(getcwd(), true))
                ->isIdenticalTo(Command\Type::ANY);
    }
}

