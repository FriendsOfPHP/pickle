<?php

declare(strict_types=1);

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

namespace Pickle\Base\Pecl;

class Website
{
    /**
     * The port to be used for the test web server.
     *
     * @var int
     */
    public const TEST_PORT = 50123;

    /**
     * The base URL of the PECL website to be used.
     *
     * @var string
     *
     * @example 'https://pecl.php.net'
     * @example 'https://127.0.0.1:50123'
     */
    private $baseUrl;

    /**
     * Should we allow unsafe connections to the PECL website?
     *
     * @var bool
     */
    private $unsafe;

    public function __construct(string $baseUrl, bool $unsafe)
    {
        $this->baseUrl = $baseUrl;
        $this->unsafe = $unsafe;
    }

    /**
     * Get the base URL of the PECL website to be used.
     *
     * @example 'https://pecl.php.net'
     * @example 'https://127.0.0.1:50123'
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Should we allow unsafe connections to the PECL website?
     */
    public function isUnsafe(): bool
    {
        return $this->unsafe;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
