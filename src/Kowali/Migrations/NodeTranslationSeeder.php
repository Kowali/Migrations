<?php namespace Kowali\Migrations;

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
    protected $path;

    /**
     * Initialize the instance
     *
     * @param  string $file
     * @param  \Hpkns\FrontMatter\Parser $frontMatter
     * @return void
     */
    public function __construct(array $default = [], $strict = false, FrontMatter $frontMatter = null)
    {
        $this->frontMatter = $frontMatter ?: \App::make('front-matter');

        if( ! empty($default))
        {
            $this->withDefault($default, $strict);
        }
    }

    /**
     * Populate the instance with the content of a file
     *
     * @param  string $file
     * @return void
     */
    public function fromFile($path)
    {
        $this->path = $path;
        $locale = pathinfo($path, PATHINFO_FILENAME);
        $mime = $this->getMimeType($path);

        $this->createFromSring(file_get_contents($path), $locale, $mime);

        return $this;
    }

    /**
     * Save the seeder
     *
     * @param  string $path
     * @return void
     */
    public function save($path = null)
    {
        if($path)
        {
            $this->path = $path;
        }

        if( ! $this->path) throw new \Exception("No path provided");

        file_put_contents($path, (string)$this);
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
