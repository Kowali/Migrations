<?php namespace Kowali\Contents\Migration;

use Kowali\Contents\Models\Taxonomy;
use Kowali\Contents\Models\Term;
use Kowali\Contents\Models\Content;

class ContentMigration extends Migration {

    protected $keys = ['tid', 'user_id', 'content_id', 'order', '_content', 'content_model', 'status', 'created_at', 'updated_at', 'deleted_at'];
    protected $translation_keys= ['content_id', 'locale', 'slug', 'title', 'content', 'excerpt', 'mime_type', 'created_at', 'updated_at'];

    public function migrate()
    {
        if($this->seeder->parent)
        {
            $parent = $this->getContent($this->seeder->parent);

            if($parent === null)
            {
                throw new Exceptions\DependenceNotFoundException;
            }
            $this->seeder->content_id = $parent->id;
        }

        $seed_data = $this->seeder->only($this->keys);

        if($content = $this->getContent($this->seeder->tid))
        {
            $content->update($seed_data);
        }
        else
        {
            $content = $this->getContentModel()->create($seed_data);
        }

        if($this->seeder->terms)
        {
            foreach($this->seeder->terms as $taxonomy => $term)
            {
                $this->migrateTerm($content, $taxonomy, $term);
            }
        }

        $this->migrateTranslations($this->seeder, $content);
    }

    public function getTitle()
    {
        return $this->seeder->getTranslations()[0]->title;
    }

    public function migrateTerm($content, $taxonomy, $term)
    {
        $term = $this->getTaxonomyTerm($taxonomy, $term);
        if($term && ! $content->terms->contains($term))
        {

            $content->terms()->attach($term);
        }
    }

    protected $taxonomies = [];
    public function getTaxonomyTerm($taxonomy, $term)
    {
        return Term::whereSlug($term)->where('taxonomy_id', function($q) use ($taxonomy){
            $q->select('id')->from('taxonomies')->where('slug', '=', $taxonomy);
        })->first();
    }

    public function migrateTranslations($seeder, $content)
    {
        foreach($seeder->getTranslations() as $translation)
        {
            $this->migrateTranslation($translation, $content);
        }

        $translated_langs = array_map(function($e){ return $e->lang ; }, $seeder->getTranslations());

        $this->deleteObsoleteTranslations($content, $translated_langs);
    }

    public function migrateTranslation($seeder, $content)
    {
        $seeder->content_id = $content->id;
        $seeder->locale = $seeder->lang;
        $seeder->slug = $seeder->slug ?: \Str::slug($seeder->title);

        $translation_data = $seeder->only($this->translation_keys);

        if($translation = $content->getTranslation($seeder->lang, false))
        {
            $translation->update($translation_data);
        }
        else
        {
            $model = $this->getContentModel()->getTranslationModelName();
            (new $model)->create($translation_data);
        }

    }

    public function deleteObsoleteTranslations($content, $langs)
    {
        foreach($content->translations as $translation)
        {
            if( ! in_array($translation->locale, $langs))
            {
                $t->delete();
            }
        }
    }

    public function getContent($tid)
    {
        return Content::where('tid', '=', $tid)
            ->first();
    }

    public function getContentModel()
    {
        $model = $this->seeder->content_model;
        return new $model;
    }
}
