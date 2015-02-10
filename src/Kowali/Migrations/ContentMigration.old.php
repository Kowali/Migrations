<?php namespace Kowali\Migrations;

use Kowali\Contents\Models\Taxonomy;
use Kowali\Contents\Models\Term;
use Kowali\Contents\Models\Content;

class ContentMigration extends Migration {

    protected $keys = ['tid', 'user_id', 'content_id', 'order', '_content', 'content_model', 'status', 'created_at', 'updated_at', 'deleted_at'];
    protected $translation_keys= ['content_id', 'locale', 'slug', 'title', 'content', 'excerpt', 'mime_type', 'created_at', 'updated_at'];

    protected $taxonomies = [];

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

        $seed_data = $this->decorateSeeder($this->seeder)->only($this->keys);

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

    public function decorateSeeder($seeder)
    {
        if( ! $seeder->user_id)
        {
            $seeder->user = $seeder->user ?: 'thupkens@mithra.com';
            $seeder->user_id = $this->getUser($seeder->user);
        }

        if( ! $seeder->tid)
        {
            $seeder->tid = \Str::slug(basename($seeder->getFolder()));
        }

        return $seeder;
    }

    public function getUser($email)
    {
        $user = \Home\Models\User::whereEmail($email)->first();
        if($user)
        {
            return $user->id;
        }

        throw new \Exception("User {$email} not found");
    }

    public function getTitle()
    {
        return $this->seeder->getTranslations()[0]->title;
    }

    public function migrateTerm($content, $taxonomy, $term)


    public function migrateTranslations($seeder, $content)
    {
        foreach($seeder->getTranslations() as $translation)
        {
            $this->migrateTranslation($translation, $content);
        }

        $translated_langs = array_map(function($e){ return $e->locale ; }, $seeder->getTranslations());

        $this->deleteObsoleteTranslations($content, $translated_langs);
    }

    public function migrateTranslation($seeder, $content)
    {
        $seeder->content_id = $content->id;
        $seeder->locale = $seeder->locale;
        $seeder->slug = $seeder->slug ?: \Str::slug($seeder->title);

        $translation_data = $seeder->only($this->translation_keys);

        if($translation = $content->getTranslation($seeder->locale, false))
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
                $translation->delete();
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
