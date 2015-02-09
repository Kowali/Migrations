<?php namespace Kowali\Contents\Migration;

use Kowali\Migrations\NodeSeeder;

abstract class Migration {

    /**
     * A node seeder.
     *
     * @var \Kowali\Migrations\NodeSeeder
     */
    protected $seeder;

    /**
     * Initialize the instance
     *
     * @param  \Kowali\Migrations\NodeSeeder
     * @return void
     */
    public function __construct(NodeSeeder $seeder)
    {
        $this->seeder = $seeder;
    }

    /**
     * Actually migrates the content.
     *
     */
    public abstract function migrate();
}
