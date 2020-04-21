<?php
declare(strict_types=1);
namespace MyPlot\forms;

use jojoe77777\FormAPI\CustomForm;
use MyPlot\MyPlot;
use MyPlot\Plot;

abstract class ComplexMyPlotForm extends CustomForm implements MyPlotForm {
	/** @var MyPlot $plugin */
	protected $plugin;
	/** @var Plot $plot */
	protected $plot;

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

	/**
	 * @param Plot $plot
	 */
	public function setPlot(Plot $plot) : void {
		$this->plot = $plot;
	}

	/**
	 * @return Plot
	 */
	public function getPlot() : Plot {
		return $this->plot;
	}
}