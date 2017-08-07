<?php
namespace MyPlot\task;

use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

use MyPlot\MyPlot;
use MyPlot\Plot;

class ClearPlotTask extends PluginTask {
	/** @var MyPlot $plugin */
	private $plugin;

	private $level, $height, $bottomBlock, $plotFillBlock, $plotFloorBlock, $plotBeginPos, $xMax, $zMax, $maxBlocksPerTick, $pos;

	public function __construct(MyPlot $plugin, Plot $plot, $maxBlocksPerTick = 256) {
		parent::__construct($plugin);
		$this->plotBeginPos = $plugin->getPlotPosition($plot);
		$this->level = $this->plotBeginPos->getLevel();
		$plotLevel = $plugin->getLevelSettings($plot->levelName);
		$plotSize = $plotLevel->plotSize;
		$this->xMax = $this->plotBeginPos->x + $plotSize;
		$this->zMax = $this->plotBeginPos->z + $plotSize;
		$this->height = $plotLevel->groundHeight;
		$this->bottomBlock = $plotLevel->bottomBlock;
		$this->plotFillBlock = $plotLevel->plotFillBlock;
		$this->plotFloorBlock = $plotLevel->plotFloorBlock;
		$this->maxBlocksPerTick = $maxBlocksPerTick;
		$this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);
		$this->plugin = $plugin;
		$this->plugin->getLogger()->debug("Clear Task started at plot {$plot->X};{$plot->Z}");
	}

	public function onRun(int $tick) {
		$blocks = 0;
		while ($this->pos->x < $this->xMax) {
			while ($this->pos->z < $this->zMax) {
				while ($this->pos->y < 128) {
					if ($this->pos->y === 0) {
						$block = $this->bottomBlock;
					} elseif ($this->pos->y < $this->height) {
						$block = $this->plotFillBlock;
					} elseif ($this->pos->y === $this->height) {
						$block = $this->plotFloorBlock;
					} else {
						$block = Block::get(0);
					}
					$this->level->setBlock($this->pos, $block, false, false);
					$blocks++;
					if ($blocks === $this->maxBlocksPerTick) {
						$this->getOwner()->getServer()->getScheduler()->scheduleDelayedTask($this, 1);
						return;
					}
					$this->pos->y++;
				}
				$this->pos->y = 0;
				$this->pos->z++;
			}
			$this->pos->z = $this->plotBeginPos->z;
			$this->pos->x++;
		}
		foreach ($this->level->getEntities() as $entity) {
			if (($plot = $this->plugin->getPlotByPosition($entity)) != null) {
				if ($plot->X === $this->plotBeginPos->x and $plot->Z === $this->plotBeginPos->z) {
					if (!$entity instanceof Player) {
						$entity->close();
					}
				}
			}
		}
		$this->plugin->getLogger()->debug("Clear task completed at {$this->plotBeginPos->x};{$this->plotBeginPos->z}");
	}
}