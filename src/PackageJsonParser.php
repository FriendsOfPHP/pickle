<?php
namespace Pickle;

class PackageJsonParser
{
    use GitIgnore;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $archive_name;

    /**
     * @var string
     */
    private $pkg;

    /**
     * Constructor
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->pkg = json_decode(file_get_contents($path));
    }

    /**
     * get files
     *
     * @return array
     */
    public function getFiles()
    {
        $ignorefiles = $this->getGitIgnoreFiles();
        $all = glob($this->path . '/*');
        $files = array_diff($all, $ignorefiles);

        return $files;
    }
}
