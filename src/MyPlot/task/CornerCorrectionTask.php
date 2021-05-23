<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class CornerCorrectionTask extends Task{

	/** @var MyPlot $plugin */
	protected $plugin;
	/** @var Plot $start */
	protected $start;
	/** @var Level|null $level */
	protected $level;
	/** @var int $height */
	protected $height;
	/** @var Block $plotWallBlock */
	protected $plotWallBlock;
	/** @var int $maxBlocksPerTick */
	protected $maxBlocksPerTick;
	/** @var Position|Vector3|null $plotBeginPos */
	protected $plotBeginPos;
	/** @var int $xMax */
	protected $xMax;
	/** @var int $zMax */
	protected $zMax;
	/** @var int $direction */
	protected $direction;
	/** @var Block $roadBlock */
	protected $roadBlock;
	/** @var Block $groundBlock */
	protected $groundBlock;
	/** @var Block $bottomBlock */
	protected $bottomBlock;
	/** @var Vector3 $pos */
	protected $pos;

	public function __construct(MyPlot $plugin, Plot $start, Plot $end, int $cornerDirection, int $maxBlocksPerTick = 256) {
		$this->plugin = $plugin;
		$this->start = $start;
		$this->plotBeginPos = $plugin->getPlotPosition($start, false);
		$this->level = $this->plotBeginPos->getLevelNonNull();
		$this->maxBlocksPerTick = $maxBlocksPerTick;

		$plotLevel = $plugin->getLevelSettings($start->levelName);
		$plotSize = $plotLevel->plotSize;
		$roadWidth = $plotLevel->roadWidth;
		$this->height = $plotLevel->groundHeight;
		$this->plotWallBlock = $plotLevel->wallBlock;
		$this->roadBlock = $plotLevel->plotFloorBlock;
		$this->groundBlock = $plotLevel->plotFillBlock;
		$this->bottomBlock = $plotLevel->bottomBlock;

		if(($start->Z - $end->Z) === 1) { // North Z-
			if($cornerDirection === Vector3::SIDE_EAST) {
				$this->plotBeginPos = $this->plotBeginPos->subtract(0, 0, $roadWidth);
				$this->plotBeginPos = $this->plotBeginPos->add($plotSize);
			}elseif($cornerDirection === Vector3::SIDE_WEST) {
				$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth, 0, $roadWidth);
			}
		}elseif(($start->X - $end->X) === -1) { // East X+
			if($cornerDirection === Vector3::SIDE_NORTH) {
				$this->plotBeginPos = $this->plotBeginPos->add($plotSize);
				$this->plotBeginPos = $this->plotBeginPos->subtract(0, 0, $roadWidth);
			}elseif($cornerDirection === Vector3::SIDE_SOUTH) {
				$this->plotBeginPos = $this->plotBeginPos->add($plotSize, 0, $plotSize);
			}
		}elseif(($start->Z - $end->Z) === -1) { // South Z+
			if($cornerDirection === Vector3::SIDE_EAST) {
				$this->plotBeginPos = $this->plotBeginPos->add($plotSize, 0, $plotSize);
				$this->plotBeginPos = $this->plotBeginPos->add($plotSize);
			}elseif($cornerDirection === Vector3::SIDE_WEST) {
				$this->plotBeginPos = $this->plotBeginPos->add(0, 0, $plotSize);
				$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth);
			}
		}elseif(($start->X - $end->X) === 1) { // West X-
			if($cornerDirection === Vector3::SIDE_NORTH) {
				$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth, 0, $roadWidth);
			}elseif($cornerDirection === Vector3::SIDE_SOUTH) {
				$this->plotBeginPos = $this->plotBeginPos->add(0, 0, $plotSize);
				$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth);
			}
		}
		$this->xMax = (int) ($this->plotBeginPos->x + $roadWidth);
		$this->zMax = (int) ($this->plotBeginPos->z + $roadWidth);
		$this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);
		$plugin->getLogger()->debug("Corner Correction Task started between plots {$start->X};{$start->Z} and {$end->X};{$end->Z}");
	}

	public function onRun(int $currentTick) : void {
		foreach($this->level->getEntities() as $entity) {
			if($entity->x > $this->pos->x - 1 and $entity->x < $this->xMax + 1) {
				if($entity->z > $this->pos->z - 1 and $entity->z < $this->zMax + 1) {
					if(!$entity instanceof Player) {
						$entity->flagForDespawn();
					}else{
						$this->plugin->teleportPlayerToPlot($entity, $this->start);
					}
				}
			}
		}
		$blocks = 0;
		while($this->pos->x < $this->xMax) {
			while($this->pos->z < $this->zMax) {
				while($this->pos->y < $this->level->getWorldHeight()) {
					if($this->pos->y === 0)
						$block = $this->bottomBlock;
					elseif($this->pos->y < $this->height)
						$block = $this->groundBlock;
					elseif($this->pos->y === $this->height)
						$block = $this->roadBlock;
					else
						$block = Block::get(Block::AIR);

					$this->level->setBlock($this->pos, $block, false, false);
					$this->pos->y++;

					$blocks++;
					if($blocks >= $this->maxBlocksPerTick) {
						$this->setHandler();
						$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
						return;
					}
				}
				$this->pos->y = 0;
				$this->pos->z++;
			}
			$this->pos->z = $this->plotBeginPos->z;
			$this->pos->x++;
		}

		$this->plugin->getLogger()->debug("Corner Correction Task completed");
	}
}