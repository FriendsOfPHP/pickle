<?php
$src_root    = getcwd();
$base_dir_pos = strlen($src_root);
$build_dir = $argc > 1 ? $argv[1] : getcwd() . '../';

if (file_exists($build_dir . 'pickle.phar')) {
    Phar::unlinkArchive($build_dir . 'pickle.phar');
}

$p = new Phar($build_dir . 'pickle.phar', 0, 'pickle.phar');
$p->compressFiles(Phar::GZ);
$p->setSignatureAlgorithm (Phar::SHA1);

$files = array();

$rd = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src_root, FilesystemIterator::SKIP_DOTS));
foreach ($rd as $file) {
    $path = basename($file->getPathInfo());
    if (!($path == '.git'|| $path == 'tests' || $path == 'Tests' || $path == 'bin')) {
        $files[substr($file->getPath() .'/' . $file->getFilename(), $base_dir_pos)] = $file->getPath(). '/' . $file->getFilename();
    }
}

$p->startBuffering();
$p->buildFromIterator(new ArrayIterator($files));

$content = file_get_contents(__DIR__.'/pickle');
$content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
$p->addFromString('bin/pickle', $content);

$stub = <<<'EOF'
#!/usr/bin/env php
<?php

Phar::mapPhar('pickle.phar');

require 'phar://pickle.phar/bin/pickle';

__HALT_COMPILER();
EOF;

$p->setStub($stub);
$p->stopBuffering();

$p = null;
