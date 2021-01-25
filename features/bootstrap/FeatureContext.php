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

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

define('PICKLE_TEST_PATH', sys_get_temp_dir() . '/pickle');

/**
 * @see https://github.com/Behat/Behat/blob/master/features/bootstrap/FeatureContext.php
 */
class FeatureContext implements SnippetAcceptingContext
{
    protected const PICKLE_BIN = 'bin/pickle';

    private $assert;

    private $php;

    private $dir;

    private $workingDir;

    private $process;

    public function __construct()
    {
        $this->assert = new \mageekguy\atoum\asserter\generator();
    }

    /**
     * @BeforeSuite
     * @AfterSuite
     */
    public static function clean()
    {
        if (is_dir(PICKLE_TEST_PATH)) {
            self::clearDirectory(PICKLE_TEST_PATH);
        }
    }

    /**
     * @BeforeScenario
     */
    public function prepare()
    {
        $this->dir = PICKLE_TEST_PATH . DIRECTORY_SEPARATOR . uniqid('pickle', true);
        $phpFinder = new PhpExecutableFinder();
        if (false === $php = $phpFinder->find()) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }

        $this->moveToNewPath($this->dir);
        $this->php = $php;
    }

    /**
     * @Given /^I am in the "([^"]*)" path$/
     */
    public function iAmInThePath($path)
    {
        $this->moveToNewPath($path);
    }

    /*     convert   Convert package.xml to new format
        help      Displays help for a command
        info      Display information about a PECL extension
        install   Install a php extension
        list      Lists commands
        release   Package a PECL extension for release
        validate  Validate a PECL extension */

    /**
     * @When /^I run "pickle(?: ((?:\"|[^"])*))?"$/
     */
    public function iRunPickle($argumentsString = '')
    {
        $argumentsString = strtr($argumentsString, ['\'' => '"']);
        $arguments = explode(' ', $argumentsString);
        $processArguments = [
            $this->php,
            __DIR__ . '/../../' . static::PICKLE_BIN,
        ];

        $processArguments = array_merge($processArguments, $arguments);
        $processArguments[] = '--no-ansi';
        $this->process = new Process($processArguments);

        $timeout = getenv('PICKLE_BEHAT_PROCESS_TIMEOUT');
        if ($timeout !== false) {
            $this->process->setTimeout($timeout);
        }

        $this->process->setWorkingDirectory($this->workingDir);
        $this->process->start();
        $this->process->wait();
    }

    /**
     * @Then the output should contain:
     */
    public function theOutputShouldContain(PyStringNode $text)
    {
        $this->assert->string($this->getOutput())->contains($this->getExpectedOutput($text));
    }

    /**
     * @Then /^it should (fail|pass)$/
     */
    public function itShouldFail($success)
    {
        if ($success === 'fail') {
            if ($this->getExitCode() === 0) {
                echo 'Actual output:' . PHP_EOL . PHP_EOL . $this->getOutput();
            }

            $this->assert->integer($this->getExitCode())->isGreaterThan(0);
        } else {
            if ($this->getExitCode() !== 0) {
                echo 'Actual output:' . PHP_EOL . PHP_EOL . $this->getOutput();
            }

            $this->assert->integer($this->getExitCode())->isZero;
        }
    }

    /**
     * @Then /^it should (fail|pass) with:$/
     */
    public function itShouldPassWith($success, PyStringNode $text)
    {
        $this->itShouldFail($success);
        $this->theOutputShouldContain($text);
    }

    /**
     * @Given /^(?:there is )?a file named "([^"]*)" with:$/
     */
    public function aFileNamedWith($filename, PyStringNode $content)
    {
        $content = strtr((string) $content, ["'''" => '"""']);
        $this->createFile($this->workingDir . '/' . $filename, $content);
    }

    /**
     * @Then /^"([^"]*)" file should exist$/
     */
    public function fileShouldExist($path)
    {
        $path = $this->workingDir . '/' . $path;
        $this->assert->boolean(file_exists($path))->isTrue('File ' . $path . ' does not exist');
    }

    /**
     * @Then /^"([^"]*)" file should contain:$/
     */
    public function fileShouldContain($path, PyStringNode $text)
    {
        $this->fileShouldExist($path);

        $path = $this->workingDir . '/' . $path;

        $fileContent = trim(file_get_contents($path));
        // Normalize the line endings in the output
        if ("\n" !== PHP_EOL) {
            $fileContent = str_replace(PHP_EOL, "\n", $fileContent);
        }

        $this->assert->string($fileContent)->isEqualTo($this->getExpectedOutput($text));
    }

    /**
     * @Then /^"([^"]*)" JSON file should contain:$/
     */
    public function JsonfileShouldContain($path, PyStringNode $text)
    {
        $this->fileShouldExist($path);

        $path = $this->workingDir . '/' . $path;

        $fileContent = json_decode(file_get_contents($path));
        $this->assert->object($fileContent)->isEqualTo(json_decode($text));
    }

    /**
     * @Then /^"([^"]*)" ((?:\d+\.?)+(?:RC\d*|beta\d*|alpha\d*)?) extension exists$/
     */
    public function extensionExists($name, $version)
    {
        $url = 'https://pecl.php.net/get/' . $name . '/' . $version . '?uncompress=1';
        $file = $name . '-' . $version . '.tgz';
        $dir = $this->workingDir . '/' . $name . '-' . $version;

        if (is_dir($dir) === false) {
            mkdir($dir, 0777, true);
        }
        $downloadFile = $dir . '/' . $file;
        file_put_contents($downloadFile, file_get_contents($url));
        $p = new PharData($downloadFile);
        $phar = $p->decompress('.tar');
        $phar->extractTo($dir);
    }

    private function moveToNewPath($path)
    {
        $newWorkingDir = $this->workingDir . '/' . $path;

        if (!file_exists($newWorkingDir)) {
            mkdir($newWorkingDir, 0777, true);
        }

        $this->workingDir = $newWorkingDir;
    }

    private function getExitCode()
    {
        return $this->process->getExitCode();
    }

    /**
     * @param string $filename
     * @param string $content
     */
    private function createFile($filename, $content)
    {
        $path = dirname($filename);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        file_put_contents($filename, $content);
    }

    private function getOutput()
    {
        $output = $this->process->getErrorOutput() . $this->process->getOutput();

        // Normalize the line endings in the output
        if ("\n" !== PHP_EOL) {
            $output = str_replace(PHP_EOL, "\n", $output);
        }

        return trim(preg_replace('/ +$/m', '', $output));
    }

    private function getExpectedOutput(PyStringNode $expectedText)
    {
        return strtr(
            $expectedText,
            [
                '\'\'\'' => '"""',
                '%%TMP_DIR%%' => sys_get_temp_dir(),
                '%%TEST_DIR%%' => realpath($this->dir),
            ]
        );
    }

    private static function clearDirectory($path)
    {
        $files = scandir($path);
        array_shift($files);
        array_shift($files);

        foreach ($files as $file) {
            $file = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($file)) {
                self::clearDirectory($file);
            } else {
                unlink($file);
            }
        }

        rmdir($path);
    }
}
