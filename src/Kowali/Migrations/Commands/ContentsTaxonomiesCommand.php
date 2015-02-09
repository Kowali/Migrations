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

class ContentsTaxonomiesCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'contents:taxonomy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates multiple taxonomies in one command.';

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

        $taxonomies = $this->getTaxonomies();

        foreach($taxonomies as $slug => $details)
        {
            $this->addTaxonomy($slug, $details);
        }
    }

    public function addTaxonomy($slug, $details)
    {
        $keys = ['slug' => $slug];
        if($taxonomy = $this->getTaxonomy($slug))
        {
            $taxonomy->update($keys);
        }
        else
        {
            $taxonomy = Taxonomy::create($keys);
        }

        if(isset($details['translations']))
        {
            foreach($details['translations'] as $lang => $translation)
            {
                $this->addTaxonomyTranslation($taxonomy, $lang, $translation);
            }
        }
        if(isset($details['terms']))
        {
            foreach($details['terms'] as $slug => $details)
            {
                $this->addTerm($taxonomy->id, $slug, $details);
            }
        }
    }

    public function addTerm($taxonomy_id, $slug, $details, $parent_id = null)
    {
        $keys = ['slug' => $slug, 'term_id' => $parent_id, 'taxonomy_id' => $taxonomy_id];

        if($term = $this->getTerm($taxonomy_id, $slug))
        {
            $term->update($keys);
        }
        else
        {
            $term = Term::create($keys);
        }

        if(isset($details['translations']))
        {
            foreach($details['translations'] as $lang => $data)
            {
                $this->addTermTranslation($term->id, $lang, $data);
            }
        }

        if(isset($details['children']))
        {
            foreach($details['children'] as $slug => $data)
            {
                $this->addTerm($taxonomy_id, $slug, $data, $term->id);
            }
        }
    }

    public function getTaxonomy($slug)
    {
        return Taxonomy::whereSlug($slug)->first();
    }

    public function getTaxonomyTranslation($taxonomy_id, $lang)
    {
        return TaxonomyTranslation::where('taxonomy_id', $taxonomy_id)->where('locale', $lang)
            ->first();
    }

    public function getTermTranslation($term_id, $lang)
    {
        return TermTranslation::where('term_id', $term_id)->where('locale', $lang)->first();
    }

    public function getTerm($taxonomy_id, $slug)
    {
        return Term::where('taxonomy_id', $taxonomy_id)->whereSlug($slug)->first();
    }

    public function addTaxonomyTranslation($taxonomy, $lang, $data)
    {
        $keys = array_merge(['locale'=>$lang, 'taxonomy_id'=>$taxonomy->id], $data);

        if($translation = $this->getTaxonomyTranslation($taxonomy->id, $lang))
        {
            $translation->update($keys);
        }
        else
        {
            TaxonomyTranslation::create($keys);
        }
    }
    public function addTermTranslation($term_id, $lang, $details)
    {
        $keys = array_merge(['locale'=>$lang, 'term_id'=>$term_id], $details);

        if($translation = $this->getTermTranslation($term_id, $lang))
        {
            $translation->update($keys);
        }
        else
        {
            TermTranslation::create($keys);
        }
    }

    /**
     * Return the path to the migrations folder.
     *
     */
    public function getTaxonomies()
    {
        $path = $this->option('path');
        if(file_exists($path))
        {
            return (new Parser)->parse(file_get_contents($path));
        }
        throw new \Exception("Taxonomies file not found");
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
            ['path', null, InputOption::VALUE_OPTIONAL, 'The folder that contains the contents to migrate.', "app/contents/taxonomies.yml"],
        );
    }

    }
