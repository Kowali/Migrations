<?php namespace Kowali\Migrations;

class NodeSeeder implements \ArrayAccess {

    use ArrayAccessTrait;

    /**
     * @var string
     */
    protected $folder;

    /**
     * @var string
     */
    protected $configFileName = 'conf.php';

    /**
     * A list of translations
     *
     * @var array
     */
    protected $translations = [];

    /**
     * Initialize the instance
     *
     * @param  string  $folder
     * @return void
     */
    public function __construct($folder, array $default = [], $strict = false)
    {
        $this->folder = $folder;

        $this->attributes = (array)$this->getConfiguration($folder);

        if( ! empty($default))
        {
            $this->withDefault($default, $strict);
        }

        $this->translations = $this->makeTranslations($folder);
    }

    /**
     * Get the configuration
     *
     * @param  string $folder
     * @return mixed
     */
    public function getConfiguration($folder)
    {
        return require("{$folder}/{$this->configFileName}");
    }

    /**
     * Return the translations
     *
     * @param  string $folder
     * @return array
     */
    public function makeTranslations($folder)
    {
        $translations = [];
        foreach(glob("{$folder}/*") as $file)
        {
            if(basename($file) == $this->configFileName)
            {
                continue;
            }

            $translations[] = new NodeTranslationSeeder($file);
        }
        return $translations;
    }

    /**
     * Return the translations
     *
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     *
     * @param  string $output
     * @return string
     */
    public function cleanOutput($output)
    {
        return $output;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $export = $this->cleanOutput(var_export($this->attributes, true));
        return "<?php\n\nreturn {$export};";
    }

    public function save()
    {
        file_put_contents("{$this->folder}/{$this->configFileName}", (string)$this);

        foreach($this->getTranslations() as $translation)
        {
            $translation->save();
        }
    }
}
