<?php namespace Kowali\Migrations;

interface ContentMigrationContract {

    /**
     * Get a list of content model and the name of the folder where they are located.
     *
     * @return string
     */
    public function getContentModels();

    /**
     * Migrate a content type.
     *
     * @param  string $path
     * @param  string $model
     * @return bool
     */
    public function migrateContentType($type, $model, $path);

    /**
     * Return the name of the User model.
     *
     * @return string
     */
    public function getUserModel();


}

