<?php
declare(strict_types=1);
namespace MyPlot\events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class MyPlotDisposeEvent extends MyPlotPlotEvent implements Cancellable{
	use CancellableTrait;
}