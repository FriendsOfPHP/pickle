<?php
namespace Pickle\tests\units\Package\JSON;

use atoum;
use Pickle\tests;

class Parser extends atoum
{
    public function testParse()
    {
        $this
            ->given($path = FIXTURES_DIR . '/package')
            ->if($this->newTestedInstance($path))
            ->then
                ->variable($this->testedInstance->parse())
            ->given($path = uniqid())
            ->then
                ->exception(function() use ($path) {
                    $this->newTestedInstance($path);
                })
                    ->hasMessage('Directory not found: ' . $path)
            ->given($path = FIXTURES_DIR)
            ->then
                ->exception(function() use ($path) {
                    $this->newTestedInstance($path);
                })
                    ->hasMessage('File not found: ' . $path . '/pickle.json')
        ;
    }

    public function testJsonDecodeError()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package',
                $errorMessage = uniqid(),
                $errorCode = E_USER_NOTICE,
                $this->function->json_decode = false
            )
            ->if($this->newTestedInstance($path))
            ->then
                ->exception(function() {
                    $this->testedInstance->parse();
                })
                    ->hasMessage('Failed to read ' . $path . '/pickle.json')
        ;
    }
}
