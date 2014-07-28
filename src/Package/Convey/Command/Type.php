<?php

namespace Pickle\Package\Convey\Command;

class Type
{
    const PECL = "pecl";
    const GIT = "git";
    const TGZ = "tgz";
    const SRC_DIR = "srcdir";
    const ANY = "any";

    const RE_PECL_PACKAGE = '#^
        (?:pecl/)?
        (?<package>\w+)
        (?:
            \-(?<stability>beta|stable|alpha)
            |@(?<version>(?:\d+(?:\.\d+){1,2})|(?:[1-2]\d{3}[0-1]\d[0-3]\d{1}))
        )?
    $#x';

    const RE_GIT_PACKAGE = '#^
        (?:git|https?)://.*?/
        (?P<package>\w+)
        (?:
            (?:\.git|)
            (?:\#(?P<reference>.*?)|)
        )?
    $#x';

    public static function determine($path, $remote)
    {
        if ($remote && preg_match(self::RE_PECL_PACKAGE, $path, $matches) > 0) {
            return self::PECL;
        } else if ($remote && preg_match(self::RE_GIT_PACKAGE, $path, $matches) > 0) {
            return self::GIT;
        } else if ('.tgz' == substr($path, -4) || '.tar.gz' == substr($path, -7)) {
            return self::TGZ;
        } else if (!$remote && is_dir($path)) {
            return self::SRC_DIR;
        }
        
        return self::ANY;
    }
}

