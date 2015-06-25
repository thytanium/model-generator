<?php

use Illuminate\Filesystem\Filesystem;
use Thytanium\ModelGenerator\ModelGenerator;

/**
 * @package ModelGenerator
 * @author Alejandro González thytanium@gmail.com
 * @license MIT
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
     * Class constructor
     */
    public function __construct()
    {
        $this->file = new Filesystem;
        $this->generator = new ModelGenerator($this->file);
    }
}