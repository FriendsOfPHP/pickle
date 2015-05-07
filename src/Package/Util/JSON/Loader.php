<?php

namespace Pickle\Package\Util\JSON;

use Composer\Package\Loader\LoaderInterface;

class Loader
{
    protected $loader;

    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @param string $path
     *
     * @return Pickle\Base\Interfaces\Package
     */
    public function load($path)
    {
        if (false === is_file($path)) {
            throw new \InvalidArgumentException('File not found: '.$path);
        }

        $json = @json_decode(file_get_contents($path));

        if (false === $json) {
            $error = error_get_last();
            $exception = null;

            if (null !== $error) {
                $exception = new \Exception($error['message'], $error['type']);
            }

            throw new \RuntimeException('Failed to read '.$path, 0, $exception);
        }

        $this->validate($json);

        return $this->loader->load((array) $json);
    }

    protected function validate($json)
    {
        $schema = json_decode(file_get_contents(__DIR__.'/../../../../res/pickle-schema.json'));
        $validator = new \JsonSchema\Validator();
        $validator->check($json, $schema);

        if (false === $validator->isValid()) {
            $message = '';

            foreach ($validator->getErrors() as $error) {
                $message .= sprintf('[%s] %s', $error['property'], $error['message']).PHP_EOL;
            }

            throw new \RuntimeException($message);
        }
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
