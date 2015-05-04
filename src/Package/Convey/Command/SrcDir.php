<?php

namespace Pickle\Package\Convey\Command;

use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts;

class SrcDir extends Abstracts\Package\Convey\Command implements Interfaces\Package\Convey\Command
{
    protected function prepare()
    {
        $this->url = $this->path;
    }

    public function execute($target, $no_convert)
    {
        /* Override target, otherwise we'd need to copy ext root each time */
        $target = realpath($this->path);

        $exe = DefaultExecutor::factory($this);

        return $exe->execute($target, $no_convert);
    }

    public function getType()
    {
        return Type::SRC_DIR;
    }
}
