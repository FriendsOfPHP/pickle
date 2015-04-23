<?php

namespace Pickle\Package\PHP\Util;

use Pickle\Base\Abstracts;
use Pickle\Base\Interfaces;

use Pickle\Package;
use Pickle\Package\PHP\Util\ConvertChangeLog;
use Pickle\Package\Util\JSON\Dumper;

class PackageXml
{
	protected $xmlPath = NULL;
	protected $package = NULL;

	public function __construct($path)
	{
		$names = array(
			"package2.xml",
			"package.xml",
		);

		foreach ($names $as $fl) {
			$xml = $path . DIRECTORY_SEPARATOR . $fl;
			if (true === is_file($xml)) {
				$this->xmlPath = $xml;
				break;
			}
		}

		if (!$this->xmlPath) {
		    throw new \InvalidArgumentException("The path '$path' doesn't contain package.xml");
		}
	}

	public function load()
	{
		$loader = new Package\PHP\Util\XML\Loader(new Package\Util\Loader());
		$this->package = $loader->load($this->xml);

		if (!$this->package) {
			throw new \Exception("Failed to load '{$this->xmlPath}'");
		}
	}

	public function convertChangeLog()
	{
		if (!$this->package) {
			$this->load();
		}

		$convertCl = new ConvertChangeLog($this->xml);
		$convertCl->parse();
		$convertCl->generateReleaseFile();
	}


	/* XXX maybe need a separate composer.json util */
	public function dump()
	{
		if (!$this->package) {
			$this->load();
		}

		$dumper = new Dumper();
		$dumper->dumpToFile($this->package, dirname($this->xmlPath) . DIRECTORY_SEPARATOR . 'composer.json');
	}
}

