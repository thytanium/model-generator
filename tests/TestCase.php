<?php

use Illuminate\Filesystem\Filesystem;
use Thytanium\ModelGenerator\ModelGenerator;

/**
 * @package ModelGenerator
 * @author Alejandro González thytanium@gmail.com
 * @license MIT http://opensource.org/licenses/MIT
 * @link http://www.github.com/thytanium/model-generator
 */
class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var Filesystem
     */
    protected $file;

    /**
     * @var ModelGenerator
     */
    protected $generator;

    /**
     * @var array
     */
    protected $paths;

    /**
     * Set up test case
     * @return void
     */
    protected function setUp()
    {
        $this->file = new Filesystem;
        $this->generator = new ModelGenerator($this->file);

        $this->paths = [
            'migrations' => __DIR__.'/migrations/',
            'models' => __DIR__.'/models/',
        ];
    }

    /**
     * Returns path where test migrations are located
     * @return mixed
     */
    protected function getMigrationsPath()
    {
        return $this->paths['migrations'];
    }

    /**
     * Returns path where test models are located
     * @return mixed
     */
    protected function getModelsPath()
    {
        return $this->paths['models'];
    }
}