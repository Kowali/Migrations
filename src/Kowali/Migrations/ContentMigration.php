<?php namespace Kowali\Migrations;

use Kowali\Contents\ContentRepository;
use Kowali\Contents\Models\Content;
use Kowali\Contents\Models\Taxonomy;
use Kowali\Contents\Models\Term;
use Kowali\Contents\Models\Meta;

class ContentMigration implements ContentMigrationContract {

    /**
     * @var \Kowali\Contents\ContentRepository
     */
    protected $content;

    /**
     * A list of contents to be migrated.
     *
     * @var array
     */
    protected $contentModels = [];

    /**
     * The model to use to represent users.
     *
     * @var string
     */
    protected $userModel = 'User';

    /**
     * Key used to find users.
     *
     * @var string
     */
    protected $userIdentifyingKey = 'email';

    /**
     * The key to the default user.
     *
     * @var string
     */
    protected $defaultUserId = 'nobody@dns.com';

    /**
     * A list of user with their id to help speed lookup.
     *
     * @var array
     */
    protected $userCache = [];

    /**
     * Default values for the content seeder.
     *
     * @var array
     */
    protected $seederDefaultValues = [
        'order'         => 1,
        'status'        => 'published'
    ];

    /**
     * Default values for the content seeder translations.
     *
     * @var array
     */
    protected $seederTranslationDefaultValues = [];

    /**
     * The fields used to migrate contents.
     *
     * @var array
     */
    protected $contentFields = ['tid', 'user_id', 'content_id', 'order', '_content', 'content_model', 'status', 'created_at', 'deleted_at'];

    /**
     * The fields used to migrate content translations.
     *
     * @var array
     */
    protected $contentTranslationFields = ['content_id', 'locale', 'slug', 'title', 'content', 'excerpt', 'mime_type', 'created_at'];

    /**
     * Initialize the instance.
     *
     * @param  \Kowali\Contents\ContentRepository
     * @return void
     */
    public function __construct(ContentRepository $content)
    {
        $this->content = $content;
    }

    /**
     * Get a list of content model and the name of the folder where they are located.
     *
     * @return string
     */
    public function getContentModels()
    {
        return $this->contentModels;
    }

    /**
     * @return string
     */
    public function getSeederDefaultValues()
    {
        return $this->seederDefaultValues;
    }

    /**
     *
     * @return string
     */
    public function getSeederTranslationDefaultValues()
    {
        return $this->seederTranslationDefaultValues;
    }

    /**
     * Migrate a content type.
     *
     * @param  string $path
     * @param  string $model
     * @return bool
     */
    public function migrateContentType($type, $model, $path)
    {
        $seed = (new NodeSeeder($this->getSeederDefaultValues(), $this->getSeederTranslationDefaultValues()))
            ->fromFolder($path);

        if( ! $seed->tid)
        {
            $seed->tid = \Str::slug(basename($path));
        }
        if(true ||  ! $seed->content_model)
        {
            $seed->content_model = $model;
        }

        if( ! $seed->user_id)
        {
            $seed->user_id = $this->getUserId($seed->user, $this->userIdentifyingKey);
        }

        if($seed->parent && ! $seed->content_id)
        {
            $seed->content_id = $this->content->getByTid($seed->parent)->id;
        }

        if( ! $seed->created_at)
        {
            $seed->created_at = date("Y-m-d H:i:s", time());
        }

        $content = $this->content->updateOrCreate($seed->only($this->contentFields));

        foreach($seed->getTranslations() as $translation)
        {
            $this->migrateTranslation($translation, $content);
        }

        if($seed->terms)
        {
            foreach($seed->terms as $taxonomy => $term)
            {
                $this->addTerm($taxonomy, $term, $content);
            }
        }

        if($seed->metas)
        {
            foreach($seed->metas as $name => $details){
                $this->addMeta($name, $details, $content);
            }
        }
    }

    public function migrateTranslation($seed, $content)
    {
        $seed->content_id = $content->id;
        $seed->slug = \Str::slug($seed->title);
        $data = $seed->only($this->contentTranslationFields);

        if($translation = $this->content->getTranslation($content, $seed->locale))
        {
            $translation->update($data);
        }
        else
        {
            $model = $content->getTranslationModelName();
            (new $model)->create($data);
        }
    }

    /**
     *
     * @param  string                          $taxonomy
     * @param  string                          $term
     * @param  \Kowali\Contents\Models\Content $content
     * @return void
     */
    public function addTerm($taxonomy, $term, $content)
    {
        if(is_array($term))
        {
            foreach($term as $t)
            {
                $this->addTerm($taxonomy, $t, $content);
            }
            return;
        }

        $this->content->addTerm($content, $taxonomy, $term);
    }

    /**
     * @param  string                          $name
     * @param  string                          $details
     * @param  \Kowali\Contents\Models\Content $content
     * @return void
     */
    public function addMeta($name, $meta, $content)
    {
        $keys = [
            'lang'          => $meta['lang'],
            'value'         => $meta['content'],
            'key'           => $name,
            'metaable_type' => $content->content_model,
            'metaable_id'   => $content->id,
        ];

        Meta::updateOrCreate(array_only($keys, ['lang', 'key', 'metaable_type', 'metaable_id']), $keys);
    }

    /**
     * Return a user numeric id based on the textual id.
     *
     * @param  mixed $id
     * @param  mixed $key
     * @return string
     */
    public function getUserId($id = null, $key = 'email')
    {
        $id = $id ?: $this->defaultUserId;

        if( ! array_key_exists($id, $this->userCache))
        {
            $model = $this->userModel;
            $user = (new $model)->newQuery()->where($key, '=', $id)->first();
            if( ! $user)
            {
                throw new \Exception("User {$id} not found");
            }
            $this->userCache[$id] = $user->id;
        }

        return $this->userCache[$id];
    }

    /**
     * Return the name of the User model.
     *
     * @return string
     */
    public function getUserModel()
    {
        return $this->userModel;
    }

}
