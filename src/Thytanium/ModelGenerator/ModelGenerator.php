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
        $this->path = base_path('database/migrations');
    }

    public function build()
    {
        foreach ($this->file->files($this->path) as $file) {
            $this->handle($this->file->get($file));
        }
    }

    private function handle($input)
    {
        $matches = [];
        preg_match("#(public\\s)?function\\sup\\(\\)(\\n|\\s|\\t)*\\{([^\\}]+\\}){2}#i", $input, $matches);

        if (count($matches)) {
            $up = $matches[0];
            $matches = [];
            preg_match_all("#(schema\\:\\:create\\s?\\(\\'([a-z0-9_]+)\\'\\s*\\,\\s*function\\s*\\((blueprint\\s*)?\\$([a-z_]+)\\s*\\)(\\s|\\n|\\t)*\\{[^\\}]+\\}\\)\\;(\\s|\\n|\\t)*)+#i", $up, $matches);

            if (count($matches)) {
                for ($i = 1; $i < count($matches); $i+=6) {
                    $schema = $matches[$i][0];
                    $table = $matches[$i+1][0];

                    $this->create($table);
                }
            }
        }
    }

    private function create($table, $force = false)
    {
        $paths = [
            'templates' => __DIR__.'/../../../templates',
            'models' => app_path(),
        ];

        $namespace = preg_replace("|\\\\$|", "", $this->getAppNamespace());
        $classname = ucfirst(camel_case(str_singular($table)));

        //Model template
        $model = $this->file->get($paths['templates'].'/Model.txt');
        $model = str_replace('<!--namespace-->', $namespace, $model);
        $model = str_replace('<!--classname-->', $classname, $model);
        $model = str_replace('<!--properties-->', '', $model);
        $model = str_replace('<!--relations-->', '', $model);

        //Store file
        $newfile = $paths['models'].'/'.$classname.'.php';
        if ($force || !$this->file->exists($newfile)) {
            $this->file->put($newfile, $model);
        }
    }
}