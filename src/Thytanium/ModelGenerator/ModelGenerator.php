<?php

namespace Thytanium\ModelGenerator;

use Illuminate\Filesystem\Filesystem as File;
use Illuminate\Console\AppNamespaceDetectorTrait as AppNamespace;

/**
 * Class ModelGenerator
 * @package Thytanium\ModelGenerator
 */
class ModelGenerator
{
    use AppNamespace;

    /**
     * Temp relations list
     * @var array
     */
    protected $relations;

    /**
     * @var File
     */
    protected $file;

    /**
     * Migrations path
     * @var string
     */
    protected $path;

    /**
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }


}