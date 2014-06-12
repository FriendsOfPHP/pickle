<?php
$src_root    = getcwd();
$base_dir_pos = strlen($src_root);
$build_dir = $argc > 1 ? $argv[1] : getcwd() . '../';
echo "$base_dir_pos $src_root\n";

if (file_exists($build_dir . 'pickle.phar')) {
    Phar::unlinkArchive($build_dir . 'pickle.phar');
}

$p = new Phar($build_dir . 'pickle.phar', 0, 'pickle.phar');
$p->compressFiles(Phar::GZ);
$p->setSignatureAlgorithm (Phar::SHA1);

$files = array();
$files['stub.php']='pickle.php';
 
$rd = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src_root, FilesystemIterator::SKIP_DOTS));
foreach($rd as $file) {
	$path = basename($file->getPathInfo());
	if (!($path == '.git'|| $path == 'tests' || $path == 'Tests')) {
	
		echo basename($file->getPathInfo()) . "\n";
		$files[substr($file->getPath() .'/' . $file->getFilename(), $base_dir_pos)] = $file->getPath(). '/' . $file->getFilename();
		//print_r(substr($file->getPath() .'/' . $file->getFilename(), $base_dir_pos) . "\n");
	}
}

$p->startBuffering();
$p->buildFromIterator(new ArrayIterator($files));
$p->stopBuffering();

$p->setStub($p->createDefaultStub('pickle.php'));
$p = null;