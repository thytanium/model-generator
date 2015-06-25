<?php

namespace Thytanium\ModelGenerator\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Thytanium\ModelGenerator\ModelGenerator;

/**
 * @package ModelGenerator
 * @author Alejandro González thytanium@gmail.com
 * @license MIT
 * @link http://www.github.com/thytanium/model-generator
 */
class GenerateModels extends Command
{
    /**
     * @var
     */
    protected $generator;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'g:models';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generate (Eloquent) models';

    /**
     * Create a new command instance.
     * @param ModelGenerator $generator
     */
	public function __construct(ModelGenerator $generator)
	{
		parent::__construct();

        $this->generator = $generator;
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
        //First round
		$this->generator->firstRound($this->option('migrations'));

        //Ask for relations
        $this->askRelations();

        //Ask for pivots
        $this->askPivots();

        //Second round
        $this->generator->secondRound($this->option('models'), $this->option('namespace'));

		$this->info("Models generated successfully.");
	}

    /**
     * Ask which tables are pivots and which are not
     */
    private function askPivots()
    {
        foreach ($this->generator->pivots as $k => $pivot) {
            //If it's only one choice
            if (count($pivot) == 1) {
                $table = implode("_", $pivot[0]);
                if (!$this->confirm("Is `{$table}` a pivot table?", true)) {
                    $this->generator->regulars[] = $table;
                    unset($pivot);
                }
            }
            //If there's more than one choice
            else {
                $confirmed = false;
                $uncertain = [];

                //First round
                for ($i = 0; $i < count($pivot) && $confirmed; $i++) {
                    $table = implode("_", $pivot[$i]);
                    if ($this->confirm("Is `{$table}` a pivot table?", true)) {
                        $confirmed = true;
                    }
                    else {
                        $uncertain[] = $table;
                    }
                }

                //Confirmed, forget about the other ones
                if ($confirmed) {
                    for ($j = $i+1; $j < count($pivot); $j++) {
                        unset($pivot[$j]);
                    }
                }
                //None of them are pivots
                else {
                    unset($pivot);
                }

                //Second round
                $confirmed = false;
                for ($i = 0; $i < count($uncertain) && $confirmed; $i++) {
                    $table = $uncertain[$i];
                    if ($this->confirm("Is `{$table}` a regular table?", true)) {
                        $this->generator->regulars[] = $table;
                        $confirmed = true;
                    }
                }
            }
        }

        //Re-arrange pivots
        foreach ($this->generator->pivots as $k => $pivot) {
            $this->generator->pivots[$k] = array_values($pivot);
        }
    }

    /**
     * Ask to the developer which relations are OTO and which are OTM
     */
    private function askRelations()
    {
        foreach ($this->generator->relations as $relation) {
            //Ask
            $choice = $this->ask("Is the relation between `".$relation['table']."(".$relation['local_id'].")`"
                                    ."and `".$relation['foreign_table']."(".$relation['foreign_id'].")`"
                                    ."1:One to Many? or 2:One to One? or 3:None", '1');

            if ($choice != '3') {
                $choice == '2' ? $this->generator->oneToOne[] = $relation : $this->generator->oneToMany[] = $relation;
            }
        }
    }

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			//['example', InputArgument::REQUIRED, 'An example argument.'],
		];
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['migrations', null, InputOption::VALUE_OPTIONAL, 'Path where migrations are located.', database_path('migrations')],
			['models', null, InputOption::VALUE_OPTIONAL, 'Path where models will be placed.', app_path()],
			['namespace', null, InputOption::VALUE_OPTIONAL, 'Optional namespace.', null],
		];
	}

}
