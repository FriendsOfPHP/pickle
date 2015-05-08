<?php

namespace Pickle\Base\Abstracts;

use Composer\Package\CompletePackage;

class Package extends CompletePackage
{
    public function getSimpleName()
    {
        $full_name = $this->getName();

        if (!preg_match(",(.+)/(.+),", $full_name, $m)) {
            return $full_name;
        }

        return $m[2];
    }

    public function getVendorName()
    {
        $full_name = $this->getName();

        if (!preg_match(",(.+)/(.+),", $full_name, $m)) {
            return "";
        }

        return $m[1];
    }

    public function getUniqueNameForFs()
    {
        return sha1($this->getUniqueName());
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
