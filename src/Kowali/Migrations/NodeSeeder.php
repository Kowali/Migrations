<?php namespace Kowali\Migrations;

use Symfony\Component\Yaml\Yaml;

class NodeSeeder implements \ArrayAccess {
    use ArrayAccessTrait;

    /**
     * @var string
     */
    protected $folder;

    /**
     * @var string
     */
    protected $configFileName = 'conf';

    /**
     * A list of accepted format
     *
     * @var array
     */
    protected $confFormats = ['json', 'yml', 'php'];

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
    public function __construct(array $default = [])
    {
        if( ! empty($default))
        {
            $this->withDefault($default);
        }
    }

    /**
     * Populate the seeder with the content of a folder
     *
     * @param  string $folder
     * @return NodeSeeder
     */
    public function fromFolder($folder)
    {
        $this->folder = $folder;

        if( ! file_exists($folder) || ! is_dir($folder))
        {
            throw new \Exception("Folder {$folder} not found");
        }

        if($configuration = $this->getConfiguration($folder))
        {
            $this->withDefault((array)$configuration);
        }
        else throw new \Exception("No config found in folder {$folder}");

        if($translations = $this->readTranslations($folder))
        {
            $this->translations = $translations;
        }

        return $this;
    }

    /**
     * Get the configuration
     *
     * @param  string $folder
     * @return mixed
     */
    public function getConfiguration($folder)
    {
        foreach($this->confFormats as $format)
        {
            $path = "{$folder}/{$this->configFileName}.{$format}";

            if( ! file_exists($path)) continue;

            if($format == 'php')
            {
                return require($path);
            }

            $content = file_get_contents($path);

            if($format == 'json')
            {
                return json_decode($content);
            }

            if($format == 'yml')
            {
                return Yaml::parse($content);
            }
        }
    }

    /**
     * Return the translations
     *
     * @param  string $folder
     * @return array
     */
    public function readTranslations($folder)
    {
        $translations = [];
        foreach(glob("{$folder}/*") as $file)
        {
            if(strpos(basename($file), $this->configFileName) === 0)
            {
                continue;
            }

            $translations[] = ( new NodeTranslationSeeder() )->fromFile($file);
        }
        return $translations;
    }

    /**
     * Add a translation
     *
     * @param  array $default
     * @return void
     */
    public function addTranslation(array $default = [])
    {
        $this->translations[] = new NodeTranslationSeeder($default);
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


    public function __toString()
    {
        return $this->toString('json');
    }


    public function simplify(array $input)
    {
        foreach($input as $key => $value)
        {
            if(is_object($value) && $value instanceof \stdClass)
            {
                $input[$key] = (array)$value;
            }
            if(is_array($input[$key]))
            {
                $input[$key] = $this->simplify($input[$key]);
            }
        }
        return $input;
    }

    /**
     * @return string
     */
    public function toString($format = 'json')
    {
        $content = $this->simplify($this->attributes);

        if($format == 'php')
        {
            $export = $this->cleanOutput(var_export($content, true));
            return "<?php\n\nreturn {$export};";
        }
        elseif($format == 'yml' || $format == 'yaml')
        {
            return Yaml::dump($content, 50);
        }
        else // Json
        {
            return json_encode($content);
        }
    }

    /**
     * Return the extension corresponding to the format
     *
     * @param  string $format
     * @return string
     */
    public function getExtension($format)
    {
        switch($format)
        {
        case 'yaml':
        case 'yml':
            return 'yml';
        case 'php':
            return 'php';
        default:
            return 'json';
        }
    }

    /**
     * Save the seeder
     *
     * @param  string $format
     * @return void
     */
    public function save($folder = null, $format = 'json')
    {
        if($folder)
        {
            $this->folder = $folder;
        }

        if( ! $this->folder) throw new \Exception("Cannot determin output folder");

        if( ! file_exists($this->folder)) mkdir($folder);

        $path = "{$this->folder}/{$this->configFileName}." . $this->getExtension($format);

        file_put_contents($path, $this->toString($format));

        foreach($this->getTranslations() as $translation)
        {
            $path = "{$this->folder}/{$translation->locale}.md";
            $translation->save($path);
        }
    }
}
