<?php namespace Mithra\Needle\Migrations;

use Symfony\Component\Yaml\Yaml;

class NodeTranslationSeeder implements \ArrayAccess {

    use ArrayAccessTrait;

    /**
     * A front-matter format parser
     *
     * @var \Hpkns\FrontMatter\Parser
     */
    protected $frontMatter;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * Initialize the instance
     *
     * @param  string $file
     * @param  \Hpkns\FrontMatter\Parser $frontMatter
     * @return void
     */
    public function __construct($file = null, FrontMatter $frontMatter = null)
    {
        $this->frontMatter = $frontMatter ?: \App::make('front-matter');

        if( ! is_null($file))
        {
            $this->createFromFile($file);
            $this->setFileName($file);
        }
    }

    /**
     * Set the filename
     *
     * @param  string $file_name
     * @return void
     */
    public function setFileName($file_name)
    {
        $this->fileName = $file_name;
    }

    /**
     * Return the filename
     *
     * @return string
     */
    public function getFileName()
    {
        if($this->fileName)
        {
            return $this->fileName;
        }

        throw new \Exception("No path provided to save the translation");
    }

    /**
     * Save the seeder
     *
     * @param  string $path
     * @return void
     */
    public function save($filepath = null)
    {
        $file = $filepath ?: $this->getFileName();

        file_put_contents($file, (string)$this);
    }

    /**
     * Populate the instance with the content of a file
     *
     * @param  string $file
     * @return void
     */
    public function createFromFile($file)
    {
       $locale = pathinfo($file, PATHINFO_FILENAME);
       $mime = $this->getMimeType($file);

       $this->createFromSring(file_get_contents($file), $locale, $mime);
    }

    /**
     * Populate the instance with the content of a string
     *
     * @param  string $file
     * @param  string $locale
     * @param  string $mime
     * @return void
     */
    public function createFromSring($content, $locale = null, $mime = null)
    {
        $this->attributes = $this->frontMatter->parse($content, [
            'locale' => $locale,
            'mime_type' => $mime,
        ]);

        return $this->attributes;
    }

    /**
     * Return the file mime-type
     *
     * @param  string $file
     * @return string
     */
    public function getMimeType($file)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file);
        finfo_close($finfo);

        return $mime;
    }

    /**
     * Convert the instance to a string
     *
     * @return string
     */
    public function __toString()
    {
        $dump = Yaml::dump($this->except('content'));
        return "---\n{$dump}---\n{$this->content}";
    }
}
