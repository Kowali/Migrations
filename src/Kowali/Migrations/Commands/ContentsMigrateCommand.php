<?php namespace Kowali\Migrations\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Parser;

use \File;

use Kowali\Migrations\ContentMigrationContract;

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
     *
     * @var Kowali\Migrations\ContentMigrationContract;
     */
    protected $migration;

    /**
     * A list of contents that where not successfully migrated in the first pass.
     *
     * @see ContentsMigrateCommand::retry()
     * @var array
     */
    protected $errors = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ContentMigrationContract $migration = null)
    {
        parent::__construct();
        $this->migration = $migration ?: \App::make('Kowali\Migrations\ContentMigrationContract');
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
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $path = $this->getPath();

        foreach($this->migration->getContentModels() as $type => $model)
        {
            if(file_exists("{$path}/{$type}"))
            $this->migrateContentType($type, $model, "{$path}/{$type}");
        }

        $this->retry();
    }

    /**
     * Migrate a folder according to a type of file.
     *
     * @param  string $type
     * @param  string $model
     * @param  string $folder
     * @return void
     */
    public function migrateContentType($type, $model, $path)
    {
        foreach(File::directories($path) as $folder)
        {
            $this->migrateContent($type, $model, $folder);
        }
    }

    /**
     * Migrate a content.
     *
     * @param  string $type
     * @param  string $model
     * @param  string $folder
     * @return void
     */
    public function migrateContent($type, $model, $path)
    {
        if($this->migration->migrateContentType($type, $model, $path) !== false)
        {
            $this->info("Migrated content {$path}");
        }
        else
        {
            $this->errors[] = [$type, $model, $path];
            $this->error("Could not migrate {$path}. Will try later");
        }
    }

    /**
     * Try to migrate remaining contents.
     *
     * @return void
     */
    public function retry()
    {
        $i = 0;
        while($content = array_shift($this->errors))
        {
            if($this->migration->migrateContentType($content[0], $content[1], $content[2]) !== false)
            {
                $this->info("Migrated content {$content[2]}");
            }
            else
            {
                $this->errors[] = $content;
                $this->error("Could not migrate {$content[2]}. Will try later");
            }

            if($i++ >= 1000)
            {
                throw new \Exception('Too many rounds. Aborting');
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
