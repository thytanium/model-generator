<?php

namespace Thytanium\ModelGenerator;

use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Filesystem\Filesystem as File;
use Illuminate\Support\Collection;

/**
 * @package ModelGenerator
 * @author Alejandro González thytanium@gmail.com
 * @license MIT http://opensource.org/licenses/MIT
 * @link http://www.github.com/thytanium/model-generator
 */
class ModelGenerator
{
    use AppNamespaceDetectorTrait;

    /**
     * @var File
     */
    protected $file;

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
     * One to one relations
     * @var array
     */
    public $oneToOne = [];

    /**
     * One to many relations
     * @var array
     */
    public $oneToMany = [];

    /**
     * Temporary relations
     * @var array
     */
    public $relations = [];

    /**
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * First round
     * @param $path
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function firstRound($path)
    {
        foreach ($this->file->files($path) as $file) {
            //Extension .php
            if (preg_match("|\\.php$|", $file)) {
                $this->handle($this->file->get($file));
            }
        }
    }

    /**
     * Second round (pivots)
     * @param $path
     * @param $namespace
     * @param bool $force
     */
    public function secondRound($path, $namespace, $force = false)
    {
        //Namespace
        if (empty($namespace)) {
            $namespace = preg_replace("|\\\\$|", "", $this->getAppNamespace());
        }

        //Create regulars
        foreach ($this->regulars as $table => $options) {
            $this->create(
                $table,
                $path,
                $namespace,
                $options['fillable'],
                $options['rules'],
                $this->searchRelations($table, $namespace),
                $force
            );
        }
    }

    /**
     * Handle migration file
     * @param $input
     */
    private function handle($input)
    {
        $matches = [];
        preg_match_all("#(schema\\:\\:create\\s?\\([\\'\\\"]([a-z0-9_]+)[\\'\\\"]\\s*\\,\\s*function\\s*\\((\\s*blueprint\\s*)?\\$([a-z_]+)\\s*\\)[\\s\\n\\t]*\\{[^\\}]+\\}\\)\\;)+#i", $input, $matches);

        if (count($matches) && array_key_exists(2, $matches)) {
            //Tables in this migration
            for ($i = 0; $i < count($matches[2]); $i++) {
                $schema = $matches[0][$i];
                $table = $matches[2][$i];
                $fields = $this->fields($schema);

                //Get combinations of dashed tables
                $combinations = self::dashCombinations($table);

                //Tables without dashes
                if (count($combinations) > 0) {
                    //Look for pivot tables
                    $pivot = $this->detectPivotTable($combinations);

                    //If no pivot is found, create table
                    if (count($pivot) == 0) {
                        //Regular table
                        $this->regulars[$table] = [
                            'fillable' => $this->fillable($fields->lists('field')),
                            'rules' => $this->rules($fields, $table),
                        ];

                        //Relations
                        $this->relations = array_merge(
                            $this->relations,
                            $this->relations($schema, $table)
                        );
                    }
                    //Add temporary pivot table
                    //to ask the developer later
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
     * @param $path
     * @param $namespace
     * @param string $fillable
     * @param string $rules
     * @param string $relations
     * @param bool $force
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function create($table, $path, $namespace, $fillable = "", $rules = "", $relations = "", $force = false)
    {
        $paths = [
            'templates' => __DIR__.'/../../../templates',
            'models' => $path,
        ];

        $classname = ucfirst(camel_case(str_singular($table)));

        //Model template
        $model = $this->file->get($paths['templates'].'/Model.txt');
        $model = str_replace('<!--namespace-->', $namespace, $model);
        $model = str_replace('<!--classname-->', $classname, $model);
        $model = str_replace('<!--fillable-->', $fillable, $model);
        $model = str_replace('<!--rules-->', $rules, $model);
        $model = str_replace('<!--relations-->', $relations, $model);

        //Store file
        $newfile = $paths['models'].'/'.$classname.'.php';
        if ($force || !$this->file->exists($newfile)) {
            $this->file->put($newfile, $model);
        }
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
                array_key_exists(str_plural($tables[0]), $this->regulars) &&
                array_key_exists(str_plural($tables[1]), $this->regulars)) {
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
        $matches = $fields = [];
        preg_match_all("#\\$\\w+\\-\\>(string|(tiny|small|medium|big|long)?(text|integer)|enum|binary|boolean|char|date|datetime|decimal|double|float|time)\\s*\\(\\s*[\\'\\\"]\\s*(\\w+)[\\'\\\"]\\s*\\,?\\s*(\\[?[\\'\\\"\\w\\,\\s]*\\]?)\\s*\\)(\\s|\\n|\\t)*(\\-\\>(unsigned|unique|nullable|default)\\(\\)(\\;|\\n|\\t|\\s)*)?(\\-\\>(unsigned|unique|nullable|default)\\(\\)[\\;\\n\\t\\s]*)?#i", $input, $matches);

        if (count($matches) && array_key_exists(4, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $fields[] = [
                    'type' => $matches[1][$i],
                    'field' => $matches[4][$i],
                    'size' => self::clean($matches[5][$i]),
                    'options' => [
                        array_key_exists(8, $matches) ? $matches[8][$i] : "",
                        array_key_exists(11, $matches) ? $matches[11][$i] : "",
                    ],
                ];
            }

            return new Collection($fields);
        }

        return new Collection;
    }

    /**
     * Creates fillable array form
     * @param $input
     * @return string
     */
    private function fillable($input) {
        //In Illuminate\Support >=5.1
        //Collection->lists() returns a Collection
        //not an array
        if (is_object($input) && is_a($input, '\Illuminate\Support\Collection')) {
            $input = $input->toArray();
        }

        $input = array_map(function($i) {
            return '"'.$i.'"';
        }, $input);

        return "\n\t\t".implode(",\n\t\t", $input)."\n\t";
    }

    /**
     * Creates basic validation rules
     * @param $input
     * @param $table
     * @return string
     */
    private function rules($input, $table)
    {
        $rules = [];

        $input->each(function($field) use (&$rules, $table) {
            $aux = [];

            //Required
            if (!in_array("nullable", $field['options'])) {
                $aux[] = 'required';
            }

            //Integer
            if (preg_match("|integer$|i", $field['type'])) {
                $aux[] = 'integer';
            }
            //Double
            else if ($field['type'] == 'double') {
                $aux[] = 'numeric';
            }
            //Date
            else if (in_array($field['type'], ['date', 'dateTime', 'timestamp'])) {
                $aux[] = 'date';
            }
            //Email
            else if ($field['field'] == 'email') {
                $aux[] = 'email';
            }

            //Max or enum
            if (strlen($field['size'])) {
                $aux[] = (is_numeric($field['size']) ? 'max:' : 'in:') . $field['size'];
            }

            //Unique
            if (in_array("unique", $field['options'])) {
                $aux[] = 'unique:'.$table;
            }

            $rules[] = '"'.$field['field'].'" => "'.implode($aux, "|").'"';
        });

        return "\n\t\t".implode(",\n\t\t", $rules)."\n\t";
    }

    /**
     * Look for relations
     * @param $input
     * @param $table
     * @return array
     */
    private function relations($input, $table)
    {
        $matches = $relations = [];

        preg_match_all("#\\$\\w+\\-\\>foreign\\s*\\(\\s*\\'(\\w+)\\'\\s*\\)[\\s\\n\\t]*\\-\\>references\\s*\\(\\s*\\'(\\w+)\\'\\s*\\)[\\s\\n\\t]*\\-\\>on\\s*\\(\\s*\\'(\\w+)\\'\\s*\\)#i", $input, $matches);

        if (count($matches) == 4) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $relations[] = [
                    'table' => $table,
                    'local_id' => $matches[1][$i],
                    'foreign_id' => $matches[2][$i],
                    'foreign_table' => $matches[3][$i],
                ];
            }
        }

        return $relations;
    }

    /**
     * Process relations
     * @param $table
     * @param $namespace
     * @return array
     */
    private function searchRelations($table, $namespace)
    {
        $belongsOTO = $this->belongs(self::filter('table', $table, $this->oneToOne), $namespace);
        $belongsOTM = $this->belongs(self::filter('table', $table, $this->oneToMany), $namespace);
        $hasOTO = $this->has(self::filter('foreign_table', $table, $this->oneToOne), 1, $namespace);
        $hasOTM = $this->has(self::filter('foreign_table', $table, $this->oneToMany), 2, $namespace);
        $belongsTM = $this->pivots($table, $namespace);

        return implode("\n\n", array_merge(
            $belongsOTO,
            $belongsOTM,
            $hasOTO,
            $hasOTM,
            $belongsTM
        ));
    }

    /**
     * Process "belongs" relations
     * @param $relations
     * @param $namespace
     * @return array
     */
    private function belongs($relations, $namespace)
    {
        //Path to templates
        $path = __DIR__.'/../../../templates/';
        $output = [];

        //BelongsTo
        $relations->each(function ($r) use (&$output, $path, $namespace) {
            //Convert table name to model name
            $table = $r['foreign_table'];
            $singularTable = str_singular($table);
            $model = ucfirst(camel_case($singularTable));

            //Options
            $field = preg_replace("|_id$|", "", $r['local_id']);
            $options = $field != $singularTable ? ", '".$r['local_id']."'" : "";

            //Open template file
            $str = $this->file->get($path."BelongsTo.txt");

            //Replace fields
            $str = str_replace("<!--function-->", str_singular($table), $str);
            $str = str_replace("<!--model-->", $model, $str);
            $str = str_replace("<!--options-->", $options, $str);
            $str = str_replace("<!--namespace-->", $namespace, $str);

            $output[] = $str;
        });

        return $output;
    }

    /**
     * Process "has" relations
     * Mode 1 = One to One
     * Mode 2 = One to Many
     * @param $relations
     * @param $mode
     * @param $namespace
     * @return array
     */
    private function has($relations, $mode, $namespace)
    {
        //Path to templates
        $path = __DIR__.'/../../../templates/';
        $output = [];

        //Has
        $relations->each(function ($r) use (&$output, $path, $mode, $namespace) {
            //Convert table name to model name
            $table = $r['table'];
            $model = ucfirst(camel_case(str_singular($table)));

            //Options
            $singularTable = str_singular($r['foreign_table']);
            $field = preg_replace("|_id$|", "", $r['local_id']);
            $options = $field != $singularTable ? ", '".$r['local_id']."'" : "";

            //Open template file
            $str = $this->file->get($path."Has".($mode == 1 ? "One" : "Many").".txt");

            //Replace fields
            $str = str_replace("<!--function-->", $mode == 1 ? str_singular($table) : $table, $str);
            $str = str_replace("<!--model-->", $model, $str);
            $str = str_replace("<!--options-->", $options, $str);
            $str = str_replace("<!--namespace-->", $namespace, $str);

            $output[] = $str;
        });

        return $output;
    }

    /**
     * Process many to many relations
     * @param $table
     * @param $namespace
     * @return array
     */
    private function pivots($table, $namespace)
    {
        //Path to templates
        $path = __DIR__.'/../../../templates/';
        $output = [];

        foreach ($this->pivots as $pivot) {
            if (array_key_exists(0, $pivot) && in_array(str_singular($table), $pivot[0])) {
                //Model name
                $function = str_plural($pivot[0][0] == str_singular($table) ? $pivot[0][1] : $pivot[0][0]);
                $model = ucfirst(camel_case($pivot[0][0] == str_singular($table) ? $pivot[0][1] : $pivot[0][0]));

                //Open template file
                $str = $this->file->get($path."BelongsToMany.txt");

                //Replace fields
                $str = str_replace("<!--function-->", $function, $str);
                $str = str_replace("<!--model-->", $model, $str);
                $str = str_replace("<!--options-->", '', $str);
                $str = str_replace("<!--namespace-->", $namespace, $str);

                $output[] = $str;
            }
        }

        return $output;
    }

    /**
     * Filter specific table from relations array
     * @param $field
     * @param $input
     * @param $haystack
     * @return static
     */
    private static function filter($field, $input, $haystack)
    {
        $temp = new Collection($haystack);

        return $temp->filter(function ($t) use ($field, $input) {
            return $t[$field] == $input;
        });
    }

    /**
     * Cleans field string
     * @param $input
     * @return mixed
     */
    private static function clean($input) {
        return str_replace(['"',"'"," ","[","]"], ["","","",""], $input);
    }
}