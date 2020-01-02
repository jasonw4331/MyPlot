<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class ClearPlotTask extends Task {
	/** @var MyPlot $plugin */
	protected $plugin;
	/** @var Plot $plot */
	protected $plot;
	/** @var \pocketmine\level\Level|null $level */
	protected $level;
	/** @var int $height */
	protected $height;
	/** @var Block $bottomBlock */
	protected $bottomBlock;
	/** @var Block $plotFillBlock */
	protected $plotFillBlock;
	/** @var Block $plotFloorBlock */
	protected $plotFloorBlock;
	/** @var \pocketmine\level\Position|null $plotBeginPos */
	protected $plotBeginPos;
	/** @var int $xMax */
	protected $xMax;
	/** @var int $zMax */
	protected $zMax;
	/** @var int $maxBlocksPerTick */
	protected $maxBlocksPerTick;
	/** @var Vector3 $pos */
	protected $pos;

	/**
	 * ClearPlotTask constructor.
	 *
	 * @param MyPlot $plugin
	 * @param Plot $plot
	 * @param int $maxBlocksPerTick
	 */
	public function __construct(MyPlot $plugin, Plot $plot, int $maxBlocksPerTick = 256) {
		$this->plugin = $plugin;
		$this->plot = $plot;
		$this->plotBeginPos = $plugin->getPlotPosition($plot);
		$this->level = $this->plotBeginPos->getLevel();
		$plotLevel = $plugin->getLevelSettings($plot->levelName);
		$plotSize = $plotLevel->plotSize;
		$this->xMax = (int)($this->plotBeginPos->x + $plotSize);
		$this->zMax = (int)($this->plotBeginPos->z + $plotSize);
		$this->height = $plotLevel->groundHeight;
		$this->bottomBlock = $plotLevel->bottomBlock;
		$this->plotFillBlock = $plotLevel->plotFillBlock;
		$this->plotFloorBlock = $plotLevel->plotFloorBlock;
		$this->maxBlocksPerTick = $maxBlocksPerTick;
		$this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);
		$this->plugin = $plugin;
		$plugin->getLogger()->debug("Plot Clear Task started at plot {$plot->X};{$plot->Z}");
	}

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void {
		foreach($this->level->getEntities() as $entity) {
			if($this->plugin->getPlotBB($this->plot)->isVectorInXZ($entity)) {
				if(!$entity instanceof Player) {
					$entity->flagForDespawn();
				}else{
					$this->plugin->teleportPlayerToPlot($entity, $this->plot);
				}
			}
		}
		$blocks = 0;
		while($this->pos->x < $this->xMax) {
			while($this->pos->z < $this->zMax) {
				while($this->pos->y < $this->level->getWorldHeight()) {
					if($this->pos->y === 0) {
						$block = $this->bottomBlock;
					}elseif($this->pos->y < $this->height) {
						$block = $this->plotFillBlock;
					}elseif($this->pos->y === $this->height) {
						$block = $this->plotFloorBlock;
					}else{
						$block = Block::get(Block::AIR);
					}
					$this->level->setBlock($this->pos, $block, false, false);
					$blocks++;
					if($blocks >= $this->maxBlocksPerTick) {
						$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
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
		foreach($this->level->getTiles() as $tile) {
			if(($plot = $this->plugin->getPlotByPosition($tile)) != null) {
				if($this->plot->isSame($plot)) {
					$tile->close();
				}
			}
		}
		$this->plugin->getScheduler()->scheduleDelayedTask(new ClearBorderTask($this->plugin, $this->plot), 1);
		$this->plugin->getLogger()->debug("Plot Clear task completed at {$this->plotBeginPos->x};{$this->plotBeginPos->z}");
	}
}