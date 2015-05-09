<?php

namespace Pickle\Package\Util;

use Pickle\Base\Interfaces;

class Dumper
{
    /**
     * @param \Pickle\Base\Interfaces\Package $package
     *
     * @return array
     */
    public function dump(Interfaces\Package $package)
    {
        $data = [];

        $data['name'] = $package->getPrettyName();

        $stability = $package->getStability();
        /* not appending stable is ok */
        $version_tail = $stability && "stable" != $stability ? "-$stability" : "";
        $data['version'] = $package->getPrettyVersion() . $version_tail;

        $data['type'] = $package->getType();

        if ($license = $package->getLicense()) {
            $data['license'] = $license;
        }

        if ($authors = $package->getAuthors()) {
            $data['authors'] = $authors;
        }

        if ($description = $package->getDescription()) {
            $data['description'] = $description;
        }

        if ($support = $package->getSupport()) {
            $data['support'] = $support;
        }

        if ($extra = $package->getExtra()) {
            $data['extra'] = $extra;
        }

        return $data;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
