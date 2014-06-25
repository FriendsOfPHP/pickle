<?php

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
    const PICKLE_BIN = 'bin/pickle';

    private $assert;
    private $php;
    private $dir;
    private $workingDir;
    private $process;

    function __construct()
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
        $this->dir = PICKLE_TEST_PATH . DIRECTORY_SEPARATOR . md5(microtime() * rand(0, 10000));

        $phpFinder = new PhpExecutableFinder();
        if (false === $php = $phpFinder->find()) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }

        $this->moveToNewPath($this->dir);
        $this->php = $php;
        $this->process = new Process(null);
    }

    private function moveToNewPath($path)
    {
        $newWorkingDir = $this->workingDir .'/' . $path;

        if (!file_exists($newWorkingDir)) {
            mkdir($newWorkingDir, 0777, true);
        }

        $this->workingDir = $newWorkingDir;
    }

    /**
     * @Given /^I am in the "([^"]*)" path$/
     */
    public function iAmInThePath($path)
    {
        $this->moveToNewPath($path);
    }

    /**
     * @When /^I run "pickle(?: ((?:\"|[^"])*))?"$/
     */
    public function iRunPickle($argumentsString = '')
    {
        $argumentsString = strtr($argumentsString, array('\'' => '"'));

        $this->process->setWorkingDirectory($this->workingDir);
        $this->process->setCommandLine(
            sprintf(
                '%s %s --no-ansi %s',
                $this->php,
                escapeshellarg(__DIR__ . '/../../' . static::PICKLE_BIN),
                $argumentsString
            )
        );

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
        if ('fail' === $success) {
            if (0 === $this->getExitCode()) {
                echo 'Actual output:' . PHP_EOL . PHP_EOL . $this->getOutput();
            }

            $this->assert->integer($this->getExitCode())->isGreaterThan(0);
        } else {
            if (0 !== $this->getExitCode()) {
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
        $content = strtr((string) $content, array("'''" => '"""'));
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

        return trim(preg_replace("/ +$/m", '', $output));
    }

    private function getExpectedOutput(PyStringNode $expectedText)
    {
        $text = strtr(
            $expectedText,
            [
                '\'\'\'' => '"""',
                '%%TMP_DIR%%' => sys_get_temp_dir(),
                '%%TEST_DIR%%' => realpath($this->dir)
            ]
        );

        return $text;
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

    /**
     * @Then /^"([^"]*)" ((?:\d+\.?)+(?:RC\d*|beta\d*|alpha\d*)?) extension exists$/
     */
    public function extensionExists($name, $version)
    {
        $url = 'http://pecl.php.net/get/' . $name . '/' . $version;
        $file = $name . '-' . $version . '.tgz';
        $dir = $this->workingDir . '/' . basename($file, '.tgz');

        if (is_dir($dir) === false) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($dir . '/' . $file, file_get_contents($url));

        $p = new PharData($dir . '/' . $file);
        $phar = $p->decompress('.tgz');
        $phar->extractTo($dir);
    }
}
