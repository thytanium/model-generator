<?php

namespace Thytanium\ModelGenerator\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Thytanium\ModelGenerator\ModelGenerator;

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
		$this->generator->firstRound();

        //Ask for pivots
        $this->askPivots();

        //Second round
        $this->generator->secondRound();

		$this->info("Models generated successfully.");
	}

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
                $regulars = [];

                //First round
                for ($i = 0; $i < count($pivot) && $confirmed; $i++) {
                    $table = implode("_", $pivot[$i]);
                    if ($this->confirm("Is `{$table}` a pivot table?", true)) {
                        $confirmed = true;
                    }
                    else {
                        $regulars[] = $table;
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
                for ($i = 0; $i < count($regulars) && $confirmed; $i++) {
                    $table = $regulars[$i];
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
			//['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
		];
	}

}
