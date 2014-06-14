<?php
namespace Pickle\tests\units;

use atoum;
use Pickle\tests;

class ConvertXml extends atoum
{
	public function testMaintainers()
	{
		$this
			->given(
				$xml = new \SimpleXMLElement(file_get_contents(FIXTURES_DIR . '/maintainers.xml')),
				$path = uniqid(),
				$this->function->file_put_contents->doesNothing()
			)
			->if($this->newTestedInstance($xml, $path))
			->when(function() {
				$this->testedInstance->maintainers();
			})
				->function('file_put_contents')
					->wasCalledWithArguments(
						$path . DIRECTORY_SEPARATOR . 'CREDITS',
						'Rasmus Lerdorf (rasmus) (rasmus@php.net) (yes)' . PHP_EOL .
						'Pierre Joye (pierre) (pierre@php.net) (yes)' . PHP_EOL .
						'Jordi Boggiano (Seldaek) (j.boggiano@seld.be) (yes)' . PHP_EOL .
						'Julien Bianchi (jubianchi) (contact@jubianchi.fr) (no)' . PHP_EOL
					)->once()
		;
	}

	public function testSummary()
	{
		$this
			->given(
				$xml = new \SimpleXMLElement(file_get_contents(FIXTURES_DIR . '/summary.xml')),
				$path = uniqid(),
				$this->function->file_put_contents->doesNothing()
			)
			->if($this->newTestedInstance($xml, $path))
			->when(function() {
				$this->testedInstance->summary();
			})
				->function('file_put_contents')
					->wasCalledWithArguments($path . DIRECTORY_SEPARATOR . 'README', 'This is a dummy package' . PHP_EOL . PHP_EOL)->once()
		;
	}

	public function testRelease()
	{
		$this
			->given(
				$xml = new \SimpleXMLElement(file_get_contents(FIXTURES_DIR . '/release.xml')),
				$path = uniqid(),
				$this->function->file_put_contents->doesNothing()
			)
			->if($this->newTestedInstance($xml, $path))
			->when(function() {
				$this->testedInstance->release();
			})
				->function('file_put_contents')
					->wasCalledWithArguments(
						$path . DIRECTORY_SEPARATOR . 'RELEASE-3.1.15',
						'Date:             2014-06-14' . PHP_EOL .
						'Package version:  3.1.15' . PHP_EOL .
						'Package state:    beta' . PHP_EOL .
						'API Version:      3.1.0' . PHP_EOL .
						'API state:        stable' . PHP_EOL . PHP_EOL .
						'Changelog:' . PHP_EOL .
						'This is a note'
					)->once()
		;
	}

	public function testChangelog()
	{
		$this
			->given(
				$xml = new \SimpleXMLElement(file_get_contents(FIXTURES_DIR . '/changelog.xml')),
				$path = uniqid(),
				$this->function->file_put_contents->doesNothing()
			)
			->if($this->newTestedInstance($xml, $path))
			->when(function() {
				$this->testedInstance->changelog();
			})
				->function('file_put_contents')
					->wasCalledWithArguments(
						$path . DIRECTORY_SEPARATOR . 'RELEASE-3.1.14',
						'Date:             2013-01-02' . PHP_EOL .
						'Package version:  3.1.14' . PHP_EOL .
						'Package state:    beta' . PHP_EOL .
						'API Version:      3.1.0' . PHP_EOL .
						'API state:        stable' . PHP_EOL . PHP_EOL .
						'Changelog:' . PHP_EOL .
						'This is a note'
					)->once()
					->wasCalledWithArguments(
						$path . DIRECTORY_SEPARATOR . 'RELEASE-2.0.0',
						'Date:             2003-07-01' . PHP_EOL .
						'Package version:  2.0.0' . PHP_EOL .
						'Package state:    stable' . PHP_EOL .
						'API Version:      2.0.0' . PHP_EOL .
						'API state:        stable' . PHP_EOL . PHP_EOL .
						'Changelog:' . PHP_EOL .
						'This is a old note'
					)->once()
		;
	}

	public function testLicense()
	{
		$this
			->given(
				$xml = new \SimpleXMLElement(file_get_contents(FIXTURES_DIR . '/license.xml')),
				$path = uniqid(),
				$this->function->file_put_contents->doesNothing()
			)
			->if($this->newTestedInstance($xml, $path))
			->when(function() {
				$this->testedInstance->license();
			})
				->function('file_put_contents')
					->wasCalledWithArguments($path . DIRECTORY_SEPARATOR . 'LICENSE', 'This package is under the following license(s):' . PHP_EOL . 'PHP License')->once()
		;
	}

	public function testGeneratorJson()
	{
		$this
			->given(
				$xml = new \SimpleXMLElement(file_get_contents(FIXTURES_DIR . '/summary.xml')),
				$path = uniqid(),
				$this->function->file_put_contents->doesNothing()
			)
			->if($this->newTestedInstance($xml, $path))
			->when(function() {
				$this->testedInstance->generateJson();
			})
				->function('file_put_contents')
					->wasCalledWithArguments(
						$path . DIRECTORY_SEPARATOR . 'pickle.json',
						json_encode(
							[
								'name' => 'dummy',
								'type' => 'extension',
								'extra' => [
									'configure-options' => []
								]
							],
							JSON_PRETTY_PRINT
						)
					)->once()
			->given($xml = new \SimpleXMLElement(file_get_contents(FIXTURES_DIR . '/summary.xml')))
			->if($this->newTestedInstance($xml, $path))
			->when(function() {
				$this->testedInstance->extsrcrelease();
				$this->testedInstance->generateJson();
			})
				->function('file_put_contents')
					->wasCalledWithArguments(
						$path . DIRECTORY_SEPARATOR . 'pickle.json',
						json_encode(
							[
								'name' => 'dummy',
								'type' => 'extension',
								'extra' => [
									'configure-options' => []
								]
							],
							JSON_PRETTY_PRINT
						)
					)->twice()
			->given($xml = new \SimpleXMLElement(file_get_contents(FIXTURES_DIR . '/extsrcrelease.xml')))
			->if($this->newTestedInstance($xml, $path))
			->when(function() {
				$this->testedInstance->extsrcrelease();
				$this->testedInstance->generateJson();
			})
				->function('file_put_contents')
					->wasCalledWithArguments(
						$path . DIRECTORY_SEPARATOR . 'pickle.json',
						json_encode(
							[
								'name' => 'dummy',
								'type' => 'extension',
								'extra' => [
									'configure-options' => [
										'enable-something' => [
											'default' => 'yes',
											'prompt' => 'Enable this dummy feature'
										],
										'enable-something-else' => [
											'default' => 'no',
											'prompt' => 'Enable this other dummy feature'
										]
									]
								]
							],
							JSON_PRETTY_PRINT
						)
					)->once()
		;
	}
} 
