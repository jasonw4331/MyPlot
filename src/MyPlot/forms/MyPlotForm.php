<?php
declare(strict_types=1);
namespace MyPlot\forms;

use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use pocketmine\form\Form;
use pocketmine\player\Player;

interface MyPlotForm extends Form{
	public function __construct(MyPlot $plugin, Player $player, ?BasePlot $plot);
}