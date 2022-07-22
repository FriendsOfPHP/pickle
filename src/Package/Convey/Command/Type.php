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

namespace Pickle\Package\Convey\Command;

use Composer\Package\Version\VersionParser;

class Type
{
    public const PICKLE = 'pickle';

    public const PECL = 'pecl';

    public const GIT = 'git';

    public const TGZ = 'tgz';

    public const SRC_DIR = 'srcdir';

    public const ANY = 'any';

    public static function match($regs, $arg, &$matches)
    {
        foreach ($regs as $reg) {
            $ret = preg_match($reg, $arg, $matches);
            if ($ret > 0) {
                return $ret;
            }
        }

        return 0;
    }

    public static function determinePickle($arg, &$matches)
    {
        $versionParser = new VersionParser();
        $res = $versionParser->parseNameVersionPairs([$arg]);
        $argPrefix = substr($arg, 0, 1);
        if ($argPrefix == '/' || $argPrefix == '.') {
            return 0;
        }
        $matches = [
            'package' => $res[0]['name'],
            'version' => $res[0]['version'] ?? '',
        ];

        return 1;
    }

    public static function determinePecl($arg, &$matches)
    {
        $reg0 = '#^
            (?:pecl/)?
            (?<package>\w+)
            (?:
                \-(?<stability>beta|stable|alpha|snapshot|devel)
            )?
        $#x';

        $reg1 = '#^
            (?:pecl/)?
            (?<package>\w+)
            (?:
                (\-|@)
                (?<version>
                    (?:
                        (?:\d+(?:\.\d+){1,3})
                        |
                        (?:[1-2]\d{3}[0-1]\d[0-3]\d{1}(?:[0-2]\d[0-5]\d(?:[0-5\d])?)?)
                    )
                    (?:[A-Za-z]+[0-9]*)?
                )
            )?
        $#x';

        return self::match([$reg0, $reg1], $arg, $matches);
    }

    public static function determineGit($arg, &$matches)
    {
        $reg0 = '#^
            (?:git|https|http|ssh|rsync|file?)(://|@).*?(/|\:)
            (?P<package>[a-zA-Z0-9\-_]+)
            (?:
                (?:\.git|)
                (?:\#(?P<reference>.*?)|)
            )?
        $#x';

        return self::match([$reg0], $arg, $matches);
    }

    public static function determine($path, $remote)
    {
        if (substr($path, -4) == '.tgz' || substr($path, -7) == '.tar.gz') {
            return self::TGZ;
        }
        $matches = null;
        if ($remote && self::determinePecl($path, $matches) > 0) {
            return self::PECL;
        }
        if ($remote && self::determineGit($path, $matches) > 0) {
            return self::GIT;
        }
        if (!$remote && is_dir($path)) {
            return self::SRC_DIR;
        }
        if (self::determinePickle($path, $matches) > 0) {
            return self::PICKLE;
        }

        return self::ANY;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
