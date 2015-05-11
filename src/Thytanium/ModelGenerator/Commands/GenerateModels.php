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
		$this->generator->build();
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
