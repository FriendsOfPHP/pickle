<?php
namespace Pickle;

class Package
{
    use GitIgnore;

    /**
     * @var string Package's root directory
     */
    private $path;

    /**
     * @var Package\JSON\Parser Package's parser
     */
    private $parser;

    /**
     *
     * @pram string $path Package's root directory
     *
     */
    public function __construct($path, Package\Parser $parser = null)
    {
        $this->path = realpath($path);

        if ($this->path === false) {
            throw new \InvalidArgumentException('Directory not found: ' . $path);
        }

        $this->parser = $parser ?: new Package\JSON\Parser($path);
        $this->parser->parse();
    }

    /**
     *
     * Get the package's root directory
     *
     * @return string
     *
     */
    public function getRootDir()
    {
        return $this->path;
    }

    public function getName()
    {
        return $this->parser->getName();
    }

    public function getVersion()
    {
        return $this->parser->getVersion();
    }

    public function getStatus()
    {
        return $this->parser->getStatus();
    }

    public function getAuthors()
    {
        return $this->parser->getAuthors();
    }

    public function getSummary()
    {
        return $this->parser->getSummary();
    }

    public function getDescription()
    {
        return $this->parser->getDescription();
    }

    public function getCurrentRelease()
    {
        return $this->parser->getCurrentRelease();
    }

    public function getPastReleases()
    {
        return $this->parser->getPastReleases();
    }

    public function getConfigureOptions()
    {
        return $this->parser->getConfigureOptions();
    }

    public function getExtraOptions()
    {
        return $this->parser->getExtraOptions();
    }

    /**
     *
     * Get files, will not return gitignore files
     *
     * @return array
     *
     */
    public function getFiles()
    {
        $ignorefiles = $this->getGitIgnoreFiles();
        $all = $files = array();
        $dir = $this->path;
        while ($dirs = glob($dir . '*')) {
            $dir .= '/*';
            $files = array_diff($all, $ignorefiles);
            if (!$all) {
                $all = $dirs;
            } else {
                $all = array_merge($all, $dirs);
            }
        }

        return $files;
    }
}
