<?php

declare(strict_types=1);

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

set_error_handler(
    static function ($errno, $errstr, $errfile, $errline): void {
        file_put_contents(dirname(__DIR__) . '/500.log', "File {$errfile} - Line: {$errline}: {$errstr}\n", FILE_APPEND);
        http_response_code(500);
        exit();
    },
    -1
);

$assetsDir = dirname(__DIR__) . '/assets';

$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, '/index.php/') === 0) {
    $requestUri = substr($requestUri, strlen('/index.php'));
}
$filesMap = [
    '/get/amqp/1.4.0' => "{$assetsDir}/amqp-1.4.0.tgz",
    '/get/apc-stable' => "{$assetsDir}/APC-3.1.9.tgz",
    '/get/apc/3.1.13' => "{$assetsDir}/APC-3.1.13.tgz",
    '/get/apcu-stable' => "{$assetsDir}/apcu-5.1.19.tgz",
    '/get/apcu-beta' => "{$assetsDir}/apcu-5.1.0.tgz",
    '/get/apcu/4.0.6' => "{$assetsDir}/apcu-4.0.6.tgz",
    '/get/memcache-beta' => "{$assetsDir}/memcache-3.0.8.tgz",
    '/get/memcache/3.0.8' => "{$assetsDir}/memcache-3.0.8.tgz",
    '/get/memcache/8.0' => "{$assetsDir}/memcache-8.0.tgz",
    '/get/mongo-stable' => "{$assetsDir}/mongo-1.6.16.tgz",
    '/get/mongo/1.5.4' => "{$assetsDir}/mongo-1.5.4.tgz",
    '/get/oci8/2.0.8' => "{$assetsDir}/oci8-2.0.8.tgz",
    '/get/sqlsrv-stable' => "{$assetsDir}/sqlsrv-5.10.1.tgz",
    '/get/swoole-stable' => "{$assetsDir}/swoole-4.6.2.tgz",
    '/get/tensor-stable' => "{$assetsDir}/tensor-2.1.4.tgz",
    '/get/yaml-stable' => "{$assetsDir}/yaml-2.2.1.tgz",
    '/get/zstd-stable' => "{$assetsDir}/zstd-0.10.0.tgz",
];
$tgzToTarMap = [
    '/get/amqp/1.4.0?uncompress=1' => "{$assetsDir}/amqp-1.4.0.tgz",
    '/get/APC/3.1.13?uncompress=1' => "{$assetsDir}/APC-3.1.13.tgz",
    '/get/apcu/4.0.6?uncompress=1' => "{$assetsDir}/apcu-4.0.6.tgz",
    '/get/imagick/3.2.0RC1?uncompress=1' => "{$assetsDir}/imagick-3.2.0RC1.tgz",
    '/get/memcache/3.0.8?uncompress=1' => "{$assetsDir}/memcache-3.0.8.tgz",
    '/get/mongo/1.5.4?uncompress=1' => "{$assetsDir}/mongo-1.5.4.tgz",
];
if (isset($filesMap[$requestUri])) {
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($filesMap[$requestUri]));
    header('Content-Disposition: attachment;filename=' . basename($filesMap[$requestUri]));
    readfile($filesMap[$requestUri]);
} elseif (isset($tgzToTarMap[$requestUri])) {
    $tgzFile = $tgzToTarMap[$requestUri];
    $pathInfo = pathinfo($tgzFile);
    $tarFile = "{$pathInfo['dirname']}/{$pathInfo['filename']}.tar";
    if (is_file($tarFile)) {
        unlink($tarFile);
    }
    $tgz = new PharData($tgzFile);
    $tgz->convertToData(Phar::TAR, Phar::NONE);
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($tarFile));
    header('Content-Disposition: attachment;filename=' . basename($tarFile));
    readfile($tarFile);
    unlink($tarFile);
} else {
    file_put_contents(dirname(__DIR__) . '/404.log', "{$requestUri}\n", FILE_APPEND);
    http_response_code(404);
}
