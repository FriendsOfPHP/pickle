<?php
namespace Pickle\tests\units\Package\JSON;

use atoum;
use Pickle\tests;

class Loader extends atoum
{
    public function testLoad()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package/pickle.json',
                $loader = new \mock\Composer\Package\Loader\LoaderInterface(),
                $this->calling($loader)->load = $package = new \mock\Composer\Package\PackageInterface()
            )
            ->if($this->newTestedInstance($loader))
            ->then
                ->object($this->testedInstance->load($path))->isIdenticalTo($package)
            ->given($path = uniqid())
            ->then
                ->exception(function () use ($path) {
                    $this->testedInstance->load($path);
                })
                    ->hasMessage('File not found: ' . $path)
        ;
    }

    public function testLoadJsonDecodeError()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package/pickle.json',
                $loader = new \mock\Composer\Package\Loader\LoaderInterface(),
                $this->function->json_decode = false
            )
            ->if($this->newTestedInstance($loader))
            ->then
                ->exception(function () use ($path) {
                    $this->testedInstance->load($path);
                })
                    ->hasMessage('Failed to read ' . $path)
                ->mock($loader)
                    ->call('load')->never
        ;
    }
}
