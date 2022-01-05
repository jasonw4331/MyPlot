<?php
declare(strict_types=1);
namespace MyPlot\forms;

use MyPlot\Plot;
use pocketmine\form\Form;

interface MyPlotForm extends Form {
	public function setPlot(?Plot $plot) : void;
}