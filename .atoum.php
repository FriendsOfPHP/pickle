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

use Atoum\PraspelExtension\Manifest;
use mageekguy\atoum\observable;
use mageekguy\atoum\observer;
use mageekguy\atoum\runner;
use mageekguy\atoum\test;
use mageekguy\atoum\visibility;

class WorkaroundForNonZeroExitCodeOnFailure implements observer
{
    private $isLastObserver = false;

    private $failed = false;

    public function handleEvent($event, observable $observable)
    {
        if ($this->isLastObserver === false && $observable instanceof runner && $event === runner::runStart) {
            $observable->removeObserver($this)->addObserver($this);
            $this->isLastObserver = true;
        }
        if (class_exists(test::class, false) && in_array($event, [test::fail, test::error, test::exception, test::runtimeException], true)) {
            $this->failed = true;
        }
        if ($observable instanceof runner && $event === runner::runStop) {
            if ($this->failed) {
                throw new RuntimeException('Atoum failed!', 1);
            }
        }
    }
}

/**
 * @var mageekguy\atoum\configurator $script
 * @var mageekguy\atoum\runner $runner
 */
$script->noCodeCoverageForNamespaces('Composer');

$script->addTestsFromDirectory(__DIR__ . '/tests/units');
$runner
    ->addExtension(new Manifest())
    ->addExtension(new visibility\extension($script))
    ->addObserver(new WorkaroundForNonZeroExitCodeOnFailure())
;
