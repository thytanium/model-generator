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
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function build()
    {
        /*foreach ($this->file->files(base_path('database/migrations')) as $file) {
            $this->handle($this->file->get($file));
        }*/
        $this->handle($this->file->get(base_path('database/migrations/2015_03_24_170539_create_store_tables.php')));
    }

    private function handle($input)
    {
        $matches = [];
        preg_match("#(public\\s)?function\\s+up\\s?\\(\\s*\\)[\\n\\t\\s]*\\{[\\n\\t\\s]*(.|\\n)+(!?\\}(.|\\n)*(public\\s)?function\\s+down\\s?\\(\\s*\\))#i", $input, $matches);

        if (count($matches)) {
            $up = $matches[0];
            $matches = [];
            preg_match_all("#(schema\\:\\:create\\s?\\(\\'([a-z0-9_]+)\\'\\s*\\,\\s*function\\s*\\((blueprint\\s*)?\\$([a-z_]+)\\s*\\)(\\s|\\n|\\t)*\\{[^\\}]+\\}\\)\\;(\\s|\\n|\\t)*)+#i", $up, $matches);

            var_dump($matches);

            if (count($matches) && array_key_exists(2, $matches)) {
                //Tables in this migration
                for ($i = 0; $i < count($matches[2]); $i++) {
                    $table = $matches[2][$i];

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