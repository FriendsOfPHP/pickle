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
     * @var Package\Parser Package definition parser
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

    /**
     * @return string
     */
    public function getName()
    {
        return $this->parser->getName();
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->parser->getVersion();
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->parser->getStatus();
    }

    /**
     * @return array
     */
    public function getAuthors()
    {
        return $this->parser->getAuthors();
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->parser->getSummary();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->parser->getDescription();
    }

    /**
     * @see Parser::getCurrentRelease
     *
     * @return array
     */
    public function getCurrentRelease()
    {
        return $this->parser->getCurrentRelease();
    }

    /**
     * @see Parser::getPastReleases
     *
     * @return array
     */
    public function getPastReleases()
    {
        return $this->parser->getPastReleases();
    }

    /**
     * @return array
     */
    public function getConfigureOptions()
    {
        return $this->parser->getConfigureOptions();
    }

    /**
     * @see Parser::getExtraOptions
     *
     * @return array
     */
    public function getExtraOptions()
    {
        return $this->parser->getExtraOptions();
    }

    /**
     * Get files, will not return gitignore files
     *
     * @return array
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
