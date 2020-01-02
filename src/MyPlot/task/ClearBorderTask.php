<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

class ClearBorderTask extends Task {
	private $plugin, $plot, $level, $height, $plotWallBlock, $plotBeginPos, $xMax, $zMax;
	public function __construct(MyPlot $plugin, Plot $plot) {
		$this->plugin = $plugin;
		$this->plot = $plot;
		$this->plotBeginPos = $plugin->getPlotPosition($plot);
		$this->level = $this->plotBeginPos->getLevel();
		$this->plotBeginPos = $this->plotBeginPos->subtract(1,0,1);
		$plotLevel = $plugin->getLevelSettings($plot->levelName);
		$plotSize = $plotLevel->plotSize;
		$this->xMax = $this->plotBeginPos->x + $plotSize + 1;
		$this->zMax = $this->plotBeginPos->z + $plotSize + 1;
		$this->height = $plotLevel->groundHeight;
		$this->plotWallBlock = $plotLevel->wallBlock;
		$plugin->getLogger()->debug("Border Clear Task started at plot {$plot->X};{$plot->Z}");
	}
	public function onRun(int $currentTick) : void {
		for($x = $this->plotBeginPos->x; $x <= $this->xMax; $x++) {
			$this->level->setBlock(new Vector3($x, $this->height + 1, $this->plotBeginPos->z), $this->plotWallBlock, false, false);
			$this->level->setBlock(new Vector3($x, $this->height + 1, $this->zMax), $this->plotWallBlock, false, false);
		}
		for($z = $this->plotBeginPos->z; $z <= $this->zMax; $z++) {
			$this->level->setBlock(new Vector3($this->plotBeginPos->x, $this->height + 1, $z), $this->plotWallBlock, false, false);
			$this->level->setBlock(new Vector3($this->zMax, $this->height + 1, $z), $this->plotWallBlock, false, false);
		}
		$this->plugin->getLogger()->debug("Border Clear Task completed");
	}
}