<?php
declare(strict_types=1);
namespace MyPlot\forms;

use jojoe77777\FormAPI\CustomForm;
use MyPlot\MyPlot;

abstract class ComplexMyPlotForm extends CustomForm implements MyPlotForm {
	/** @var MyPlot $plugin */
	protected $plugin;

	public function __construct(MyPlot $plugin, ?callable $callable) {
		$this->plugin = $plugin;
		parent::__construct($callable);
	}

	/**
	 * @return MyPlot
	 */
	public function getPlugin() : MyPlot {
		return $this->plugin;
	}

}