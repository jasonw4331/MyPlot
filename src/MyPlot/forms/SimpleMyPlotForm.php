<?php
declare(strict_types=1);
namespace MyPlot\forms;

use jojoe77777\FormAPI\SimpleForm;
use MyPlot\MyPlot;
use MyPlot\Plot;

abstract class SimpleMyPlotForm extends SimpleForm implements MyPlotForm {

	/** @var MyPlot $plugin */
	protected $plugin;
	/** @var Plot|null $plot */
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
	 * @param Plot|null $plot
	 */
	public function setPlot(?Plot $plot) : void {
		$this->plot = $plot;
	}

	/**
	 * @return Plot|null
	 */
	public function getPlot() : ?Plot {
		return $this->plot;
	}
}