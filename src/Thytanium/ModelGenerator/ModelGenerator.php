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
     * Created tables
     * @var array
     */
    protected $created = [];

    /**
     * Detected pivot tables
     * @var array
     */
    public $pivots = [];

    /**
     * False pivot tables
     * @var array
     */
    public $regulars = [];

    /**
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * First round
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function firstRound()
    {
        foreach ($this->file->files(base_path('database/migrations')) as $file) {
            $this->handle($this->file->get($file));
        }
        //$this->handle($this->file->get(base_path('database/migrations/2015_03_24_163041_create_resources_table.php')));
        //$this->handle($this->file->get(base_path('database/migrations/2015_03_24_170539_create_store_tables.php')));
    }

    /**
     * Second round (pivots)
     */
    public function secondRound()
    {
        foreach ($this->pivots as $pivot) {
            //Create pivots
        }

        //Create regulars
        foreach ($this->regulars as $table) {
            $this->create($table);
        }
    }

    /**
     * Handle migration file
     * @param $input
     */
    private function handle($input)
    {
        $matches = [];
        preg_match_all("#(schema\\:\\:create\\s?\\(\\'([a-z0-9_]+)\\'\\s*\\,\\s*function\\s*\\((\\s*blueprint\\s*)?\\$([a-z_]+)\\s*\\)(\\s|\\n|\\t)*\\{[^\\}]+\\}\\)\\;(\\s|\\n|\\t)*)+#i", $input, $matches);

        if (count($matches) && array_key_exists(2, $matches)) {
            //Tables in this migration
            for ($i = 0; $i < count($matches[2]); $i++) {
                $schema = $matches[0][$i];
                $table = $matches[2][$i];
                $fields = $this->fields($schema);

                //Get combinations of dashed tables
                $combinations = self::dashCombinations($table);

                //Tables without dashes
                if (count($combinations) > 0 && count($combinations[0]) == 1) {
                    $this->create($table, $this->fillable($fields));
                }
                else {
                    //Look for pivot tables
                    $pivot = $this->detectPivotTable($combinations);

                    //If none found, create table as it is
                    if (count($pivot) == 0) {
                        $this->create($table, $this->fillable($fields));
                    }
                    //Store posible pivot
                    //to ask user later
                    else {
                        $this->pivots[] = $pivot;
                    }
                }
            }
        }
    }

    /**
     * Create model file
     * @param $table
     * @param string $fillable
     * @param bool $force
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function create($table, $fillable = "", $force = false)
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
        $model = str_replace('<!--fillable-->', $fillable, $model);
        $model = str_replace('<!--rules-->', '', $model);
        $model = str_replace('<!--relations-->', '', $model);

        //Store file
        $newfile = $paths['models'].'/'.$classname.'.php';
        if ($force || !$this->file->exists($newfile)) {
            $this->file->put($newfile, $model);
        }

        $this->created[] = $table;
    }

    /**
     * Search for (very) possible pivot tables
     * @param $candidates
     * @return array
     */
    private function detectPivotTable($candidates)
    {
        $result = [];
        foreach ($candidates as $tables) {
            if (count($tables) == 2 &&
                in_array(str_plural($tables[0]), $this->created) &&
                in_array(str_plural($tables[1]), $this->created)) {
                $result[] = $tables;
            }
        }
        return $result;
    }

    /**
     * Find combinations for dashed table names
     * hoping to find pivot tables
     * @param $str
     * @return array
     */
    private static function dashCombinations($str)
    {
        $total = substr_count($str, "_");
        if ($total <= 1) {
            return [explode("_", $str)];
        }
        else {
            //pos stores new position
            //index stores last position
            $index = 0;
            $final = [];
            while (($pos = strpos($str, "_", $index)) !== false) {
                $index = $pos+1;
                $final[] = [
                    substr($str, 0, $pos),
                    substr($str, $index),
                ];
            }
            return $final;
        }
    }

    /**
     * Searches for fields names and types
     * @param $input
     * @return array
     */
    private function fields($input)
    {
        $matches = [];
        preg_match_all("#\\$\\w+\\-\\>(string|(tiny|small|medium|big|long)?(text|integer)|enum|binary|boolean|char|date|datetime|decimal|double|float|time)\\s*\\(\\s*\\'\\s*(\\w+)\\'\\s*\\,?\\s*([\\w]*)\\s*\\)\\s*#i", $input, $matches);

        if (count($matches) && array_key_exists(4, $matches)) {
            return $matches[4];
        }

        return [];
    }

    /**
     * Creates fillable array form
     * @param $input
     * @return string
     */
    private function fillable($input) {
        $input = array_map(function($i) {
            return "'{$i}'";
        }, $input);

        return "\n\t\t".implode(",\n\t\t", $input)."\n\t";
    }
}