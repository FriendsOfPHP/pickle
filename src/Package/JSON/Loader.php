<?php
namespace Pickle\Package\JSON;

use Composer\Package\Loader\LoaderInterface;
use Pickle\Package;

class Loader
{
    protected $loader;

    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @param string $path
     */
    public function load($path)
    {
        if (is_file($path) === false) {
            throw new \InvalidArgumentException('File not found: ' . $path);
        }

        $json = @json_decode(file_get_contents($path));

        if ($json === false) {
            $error = error_get_last();
            $exception = null;

            if (null !== $error) {
                $exception = new \Exception($error['message'], $error['type']);
            }

            throw new \RuntimeException('Failed to read ' . $path, 0, $exception);
        }

        $this->validate($json);

        return $this->loader->load((array) $json);
    }

    protected function validate($json)
    {
        $schema = json_decode(file_get_contents(__DIR__ . '/../../../res/pickle-schema.json'));
        $validator = new \JsonSchema\Validator();
        $validator->check($json, $schema);

        if ($validator->isValid() === false) {
            $message = '';

            foreach ($validator->getErrors() as $error) {
                $message .= sprintf('[%s] %s', $error['property'], $error['message']) . PHP_EOL;
            }

            throw new \RuntimeException($message);
        }
    }
}
