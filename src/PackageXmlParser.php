<?php
namespace Pickle;

class PackageXmlParser {
	public $path;

	function __construct($path = 'package.xml') {
		$this->path = $path;
	}

	function parse() {
		$sx = simplexml_load_file($this->path);
		echo "Packager Version: " . $sx['packagerversion'] . "\n";
		echo "XML Version: " . $sx['version'] . "\n";
		echo "Extension pkg: " . $sx->providesextension . "\n";
		echo "Pkg name: " . $sx->name . "\n";
		echo "Pkg version: " . $sx->changelog->release->version->release . "\n";

		if (!($sx['version'] == "2.0" || $sx['version'] == "2.1")) {
			throw new \Exception('Unsupported package.xml version, 2.0 or later only is supported');
		}
		
		if (!isset($sx->providesextension)) {
			throw new \Exception('Only extension packages are supported');
		}
		return $sx;
	}
}