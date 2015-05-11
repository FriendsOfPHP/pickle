<?php

namespace Pickle\Base\Abstracts;

use Composer\Package\CompletePackage;
use Pickle\Package\Util\Header;
use Composer\Package\Version\VersionParser;

class Package extends CompletePackage
{
    public function getSimpleName()
    {
        $full_name = $this->getName();

        if (!preg_match(',(.+)/(.+),', $full_name, $m)) {
            return $full_name;
        }

        return $m[2];
    }

    public function getVendorName()
    {
        $full_name = $this->getName();

        if (!preg_match(',(.+)/(.+),', $full_name, $m)) {
            return;
        }

        return $m[1];
    }

    public function getUniqueNameForFs()
    {
        return sha1($this->getUniqueName());
    }

    /* This might be not common for other extensions to
        other engines. Lets see if this is needed to be
        moved into the Interface so each engines package
        is forced to implement it on its own. But so far ... */
    public function updateVersion()
    {
        /* Be sure package root is set before! */
        $version = new Header\Version($this);
        $parser = new VersionParser();

        $this->version = $parser->normalize($version);
        $this->prettyVersion = (string)$version;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
