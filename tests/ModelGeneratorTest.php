<?php

/**
 * @package ModelGenerator
 * @author Alejandro González thytanium@gmail.com
 * @license MIT http://opensource.org/licenses/MIT
 * @link http://www.github.com/thytanium/model-generator
 */
class ModelGeneratorTest extends TestCase
{
    /**
     * Test that main object is present
     * @return void
     */
    public function testIsPresent()
    {
        $this->assertTrue(!is_null($this->generator));
    }

    /**
     * Test first migration file
     * @dataProvider migrationProvider
     * @param $migrationsPath
     * @param $expectedModels
     * @param $oneToOne
     * @param $oneToMany
     * @param $pivots
     * @param $variation
     * @return void
     */
    public function testMigrations($migrationsPath, $expectedModels, $oneToOne, $oneToMany, $pivots, $variation)
    {
        //First round, read migrations
        $this->generator->firstRound($this->getMigrationsPath().$migrationsPath);

        //Relations
        $this->generator->pivots = $pivots;
        $this->generator->oneToOne = $oneToOne;
        $this->generator->oneToMany = $oneToMany;

        //Second round, process relations, create models
        $this->generator->secondRound($this->getModelsPath(), "App", true);

        //Check that files were created
        foreach ($expectedModels as $model) {
            $this->assertTrue($this->file->exists($this->getModelsPath()."{$model}.php"));

            //Check their contents are what are supposed to be
            $file1 = $this->file->get($this->getModelsPath().$model.".php");
            $file2 = $this->file->get($this->getModelsPath().$migrationsPath."variation{$variation}/{$model}.txt");
            $this->assertEquals($file1, $file2);
        }
    }

    /**
     * Migration provider
     * @return array
     */
    public function migrationProvider()
    {
        $data = [];

        //Migration 001 / Variation 1
        //Normal migration
        $expectedModels = ['Language', 'User', 'UserGroup', 'Student'];
        $pivots = [[['user', 'user_group']]];
        $oneToOne = [
            ['table' => 'students', 'local_id' => 'user_id', 'foreign_id' => 'id', 'foreign_table' => 'users'],
        ];
        $oneToMany = [
            ['table' => 'users', 'local_id' => 'language_id', 'foreign_id' => 'id', 'foreign_table' => 'languages'],
        ];
        $data[] = ['migration001/', $expectedModels, $oneToOne, $oneToMany, $pivots, 1];

        //Migration 001 / Variation 2
        //Migration without pivots
        $pivots = [];
        $data[] = ['migration001/', $expectedModels, $oneToOne, $oneToMany, $pivots, 2];

        //Migration 001 / Variation 3
        //Migration without one to many
        $pivots = [[['user', 'user_group']]];
        $oneToMany = [];
        $data[] = ['migration001/', $expectedModels, $oneToOne, $oneToMany, $pivots, 3];

        //Migration 001 / Variation 4
        //Migration with only one to one
        $pivots = [];
        $oneToMany = [];
        $data[] = ['migration001/', $expectedModels, $oneToOne, $oneToMany, $pivots, 4];

        //Migration 001 / Variation 5
        //Migration without one to one
        $pivots = [[['user', 'user_group']]];
        $oneToMany = [
            ['table' => 'users', 'local_id' => 'language_id', 'foreign_id' => 'id', 'foreign_table' => 'languages'],
        ];
        $oneToOne = [];
        $data[] = ['migration001/', $expectedModels, $oneToOne, $oneToMany, $pivots, 5];

        //Migration 001 / Variation 6
        //Migration with only one to many
        $pivots = [];
        $oneToOne = [];
        $data[] = ['migration001/', $expectedModels, $oneToOne, $oneToMany, $pivots, 6];

        //Migration 001 / Variation 7
        //Migration with only pivots
        $pivots = [[['user', 'user_group']]];
        $oneToOne = $oneToMany = [];
        $data[] = ['migration001/', $expectedModels, $oneToOne, $oneToMany, $pivots, 7];

        //Migration 001 / Variation 8
        //Migration with no relations
        $oneToOne = $oneToMany = $pivots = [];
        $data[] = ['migration001/', $expectedModels, $oneToOne, $oneToMany, $pivots, 8];

        /*
         * Second set of tests
         * ===================
         */

        //Migration 002 / Variation 1
        //Removing comments from every Schema::create statement
        $expectedModels = ['User', 'UserGroup'];
        $pivots = [[['user', 'user_group']]];
        $oneToOne = $oneToMany = [];
        $data[] = ['migration002/', $expectedModels, $oneToOne, $oneToMany, $pivots, 1];

        //Migration 003 / Variation 1
        //Using double quotes instead of single quotes
        $data[] = ['migration003/', $expectedModels, $oneToOne, $oneToMany, $pivots, 1];

        return $data;
    }
}