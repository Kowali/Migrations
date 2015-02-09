<?php namespace Kowali\Contents\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Parser;

use Kowali\Contents\Models\Taxonomy;
use Kowali\Contents\Models\TaxonomyTranslation;
use Kowali\Contents\Models\Term;
use Kowali\Contents\Models\TermTranslation;
use Kowali\Contents\Models\Content;

use Kowali\Contents\Migration\ContentMigration;
use Kowali\Migrations\NodeSeeder;

class ContentsMigrateCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'contents:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates multiple contents in one command.';

    /**
     * A list of contents that have not found their parent yet.
     *
     * @var array
     */
    protected $orphans = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        \Eloquent::unguard();

        $path = $this->getPath();

        $this->exploreFolder($path);
        $this->resolveDependencyErrors();
    }

    /**
     * Return the path to the migrations folder.
     *
     */
    public function getPath()
    {
        $path = base_path() . '/' . trim($this->option('path'), '/');
        if(!file_exists($path) || !is_dir($path))
        {
            throw new \Exception("The folder {$path} does not seem to exists");
        }
        return $path;
    }

    /**
     * Recursively explore a folder until something usefull is found.
     *
     * @param  string $path
     * @return void
     */
    public function exploreFolder($path)
    {
        $content = array_filter(scandir($path), function($e){
            return ! in_array($e, ['.', '..']);
        });

        if(in_array('conf.yml', $content))
        {
            $this->migrateFolder($path);
        }

        foreach($content as $file)
        {
            $filepath = "{$path}/{$file}";

            if(is_dir($filepath)){
                $this->exploreFolder($filepath);
            }
        }
    }

    /**
     * Migrate a folder.
     *
     * @param  string $path
     * @return void
     */
    public function migrateFolder($path)
    {
        $seeder = (new NodeSeeder([
            'order'   => 0,
            'user_id' => 1,
            'status'  => 'published',
            'content_model' => "Kowali\Contents\Models\Content",
        ]))->fromFolder($path);

        $migration = new ContentMigration($seeder);
        $this->info("Migrating {$migration->getTitle()}");
        $this->migrateMigration($migration);
    }

    public function migrateMigration($migration)
    {
        try
        {
            $migration->migrate();
        }
        catch(\Kowali\Contents\Migration\Exceptions\DependenceNotFoundException $e)
        {
            $this->error("Could not migrate {$migration->getTitle()}. Will retry later");
            $this->orphans[] = $migration;
        }
    }

    /**
     * Matches contents with their parents.
     *
     * @return void
     */
    public function resolveDependencyErrors()
    {
        $i = 0;
        while(count($this->orphans))
        {
            $orphan = array_shift($this->orphans);

            $this->info("Retying to migrate {$orphan->getTitle()}");
            $this->migrateMigration($orphan);

            if($i++ > 1000)
            {
                throw new \Exception('Maximum number of tries for conflict resolution reached. Abborting');
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            ['path', null, InputOption::VALUE_OPTIONAL, 'The folder that contains the contents to migrate.', "app/contents"],
        );
    }

    }
