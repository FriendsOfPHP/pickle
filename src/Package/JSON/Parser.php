<?php
namespace Pickle\Package\JSON;

use Pickle\Package;

class Parser extends Package\Parser
{
    private $path;

    /**
     * @var \StdClass
     */
    private $json;

    private $authors;
    private $releases;

    /**
     * Constructor
     *
     * @param string $path
     */
    public function __construct($path)
    {
        parent::__construct($path);

        $this->path = realpath($this->root . '/pickle.json');

        if (false === $this->path) {
            throw new \InvalidArgumentException('File not found: ' . $this->root . '/pickle.json');
        }
    }

    /**
     * @throws \RuntimeException If pickle.json could not be read
     */
    public function parse()
    {
        $this->json = @json_decode(file_get_contents($this->path));

        if ($this->json === false) {
            $error = error_get_last();
            $exception = null;

            if (null !== $error) {
                $exception = new \Exception($error['message'], $error['type']);
            }

            throw new \RuntimeException('Failed to read ' . $this->path, 0, $exception);
        }
    }

    public function getName()
    {
        return $this->json->name;
    }

    public function getVersion()
    {
        $release = $this->getCurrentRelease();

        return $release['version'];
    }

    public function getStatus()
    {
        $release = $this->getCurrentRelease();

        return $release['status'];
    }

    public function getAuthors()
    {
        if (null === $this->authors) {
            $creditsFile = $this->root . '/CREDITS';
            if (file_exists($creditsFile) === false) {
                throw new \RuntimeException('Cannot find any CREDITS file');
            }

            $credits = file($this->root . '/CREDITS');
            $authors = [];
            foreach ($credits as $credit) {
                if (preg_match('/(.+?) \((.+?)\) \((.+?)\) \((.+?)\)/', $credit, $matches) === 0) {
                    throw new \RuntimeException('CREDITS file invalid or imcomplete');
                }

                $authors[] = [
                    'name' => $matches[1],
                    'handle' => $matches[2],
                    'email' => $matches[3],
                    'active' => $matches[4]
                ];
            }

            if (count($authors) < 1) {
                throw new \RuntimeException('CREDITS file invalid or imcomplete');
            }

            $this->authors = $authors;
        }

        return $this->authors;
    }

    public function getSummary()
    {
        $lines = file($this->root . '/README');

        return array_shift($lines);
    }

    public function getDescription()
    {
        $lines = file($this->root . '/README');
        array_shift($lines);

        return implode('', $lines);
    }

    public function getCurrentRelease()
    {
        $releases = $this->getReleases();

        return $releases[count($releases) - 1];
    }

    public function getPastReleases()
    {
        return array_slice($this->getReleases(), 0, -1);
    }

    protected function getReleases()
    {
        if (null === $this->releases) {
            $releases = glob($this->root . '/RELEASE-*');

            if (empty($releases)) {
                throw new \RuntimeException('Cannot find any RELEASE file');
            }

            $sort_version = function ($a, $b) {
                $a_release = str_replace('RELEASE-', '', basename($a));
                $b_release = str_replace('RELEASE-', '', basename($b));

                return version_compare($a_release, $b_release);
            };

            usort($releases, $sort_version);

            foreach ($releases as $release) {
                $this->releases[] = $this->formatRelease($release);
            }
        }

        return $this->releases;
    }

    protected function formatRelease($path)
    {
        $release = file_get_contents($path);
        $pattern = '/Date:\s*(?P<date>[\d\?]{4}-[\d\?]{2}-[\d\?]{2})
Package version:\s*(?P<version>.*)
Package state:\s*(?P<status>\w+)
API Version:\s*(?P<api_version>.*)
API state:\s*(?P<api_status>\w+)\s*
Changelog:
(?P<notes>.*)/i';

        if (preg_match($pattern, $release, $matches) === 0) {
            throw new \RuntimeException('Invalid RELEASE file: ' . $path);
        }

        return [
            'version' => $matches['version'],
            'status' => $matches['status'],
            'date' => $matches['date'],
            'notes' => $matches['notes'],
            'api' => [
                'version' => $matches['api_version'],
                'status' => $matches['api_status'],
            ]
        ];
    }

    public function getExtraOptions()
    {
        return $this->json->extra['configure-options'];
    }
}
