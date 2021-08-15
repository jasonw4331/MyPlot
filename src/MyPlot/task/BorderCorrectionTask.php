<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

class BorderCorrectionTask extends Task{
	protected MyPlot $plugin;
	protected Plot $start;
	protected Level $level;
	protected int $height;
	protected Block $plotWallBlock;
	/** @var Position|Vector3 $plotBeginPos */
	protected Vector3 $plotBeginPos;
	protected int $xMax;
	protected int $zMax;
	protected int $direction;
	protected Block $roadBlock;
	protected Block $groundBlock;
	protected Block $bottomBlock;
	protected Plot $end;
	protected bool $fillCorner;
	protected int $cornerDirection;
	protected Vector3 $pos;
	protected int $maxBlocksPerTick;

	public function __construct(MyPlot $plugin, Plot $start, Plot $end, bool $fillCorner = false, int $cornerDirection = -1, int $maxBlocksPerTick = 256) {
		$this->plugin = $plugin;
		$this->start = $start;
		$this->end = $end;
		$this->fillCorner = $fillCorner;
		$this->cornerDirection = $cornerDirection;
		$this->maxBlocksPerTick = $maxBlocksPerTick;

		$this->plotBeginPos = $plugin->getPlotPosition($start, false);
		$this->level = $this->plotBeginPos->getLevelNonNull();

		$plotLevel = $plugin->getLevelSettings($start->levelName);
		$plotSize = $plotLevel->plotSize;
		$roadWidth = $plotLevel->roadWidth;
		$this->height = $plotLevel->groundHeight;
		$this->plotWallBlock = $plotLevel->wallBlock;
		$this->roadBlock = $plotLevel->roadBlock;
		$this->groundBlock = $plotLevel->plotFillBlock;
		$this->bottomBlock = $plotLevel->bottomBlock;

		if(($start->Z - $end->Z) === 1) { // North Z-
			$this->plotBeginPos = $this->plotBeginPos->subtract(0, 0, $roadWidth);
			$this->xMax = (int) ($this->plotBeginPos->x + $plotSize);
			$this->zMax = (int) ($this->plotBeginPos->z + $roadWidth);
			$this->direction = Vector3::SIDE_NORTH;
		}elseif(($start->X - $end->X) === -1) { // East X+
			$this->plotBeginPos = $this->plotBeginPos->add($plotSize);
			$this->xMax = (int) ($this->plotBeginPos->x + $roadWidth);
			$this->zMax = (int) ($this->plotBeginPos->z + $plotSize);
			$this->direction = Vector3::SIDE_EAST;
		}elseif(($start->Z - $end->Z) === -1) { // South Z+
			$this->plotBeginPos = $this->plotBeginPos->add(0, 0, $plotSize);
			$this->xMax = (int) ($this->plotBeginPos->x + $plotSize);
			$this->zMax = (int) ($this->plotBeginPos->z + $roadWidth);
			$this->direction = Vector3::SIDE_SOUTH;
		}elseif(($start->X - $end->X) === 1) { // West X-
			$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth);
			$this->xMax = (int) ($this->plotBeginPos->x + $roadWidth);
			$this->zMax = (int) ($this->plotBeginPos->z + $plotSize);
			$this->direction = Vector3::SIDE_WEST;
		}else{
			throw new \Exception('Merge Plots are not adjacent');
		}

		$this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);

		$plugin->getLogger()->debug("Border Correction Task started between plots {$start->X};{$start->Z} and {$end->X};{$end->Z}");
	}

	public function onRun(int $currentTick) : void {
		$blocks = 0;
		if($this->direction === Vector3::SIDE_NORTH or $this->direction === Vector3::SIDE_SOUTH) {
			while($this->pos->z < $this->zMax) {
				while($this->pos->y < $this->level->getWorldHeight()) {
					if($this->pos->y > $this->height + 1)
						$block = BlockFactory::get(BlockIds::AIR);
					elseif($this->pos->y === $this->height + 1){
						// TODO: change by x/z coord
						$block = $this->plotWallBlock;
					}elseif($this->pos->y === $this->height)
						$block = $this->roadBlock;
					elseif($this->pos->y === 0)
						$block = $this->bottomBlock;
					else//if($y < $this->height)
						$block = $this->groundBlock;

					$this->level->setBlock(new Vector3($this->pos->x - 1, $this->pos->y, $this->pos->z), $block, false, false);
					$this->level->setBlock(new Vector3($this->xMax, $this->pos->y, $this->pos->z), $block, false, false);
					$this->pos->y++;

					$blocks += 2;
					if($blocks >= $this->maxBlocksPerTick) {
						$this->setHandler();
						$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
						return;
					}
				}
				$this->pos->y = 0;
				$this->pos->z++;
			}
		}else{
			while($this->pos->x < $this->xMax) {
				while($this->pos->y < $this->level->getWorldHeight()) {
					if($this->pos->y > $this->height + 1)
						$block = BlockFactory::get(BlockIds::AIR);
					elseif($this->pos->y === $this->height + 1)
						$block = $this->plotWallBlock; // TODO: change by x/z coord
					elseif($this->pos->y === $this->height)
						$block = $this->roadBlock;
					elseif($this->pos->y === 0)
						$block = $this->bottomBlock;
					else//if($y < $this->height)
						$block = $this->groundBlock;
					$this->level->setBlock(new Vector3($this->pos->x, $this->pos->y, $this->pos->z - 1), $block, false, false);
					$this->level->setBlock(new Vector3($this->pos->x, $this->pos->y, $this->zMax), $block, false, false);
					$this->pos->y++;
					$blocks += 2;
					if($blocks >= $this->maxBlocksPerTick) {
						$this->setHandler();
						$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
						return;
					}
				}
				$this->pos->y = 0;
				$this->pos->x++;
			}
		}

		$this->plugin->getLogger()->debug("Border Correction Task completed");
		if($this->fillCorner) $this->plugin->getScheduler()->scheduleDelayedTask(new CornerCorrectionTask($this->plugin, $this->start, $this->end, $this->cornerDirection, $this->maxBlocksPerTick), 10);

	}
}