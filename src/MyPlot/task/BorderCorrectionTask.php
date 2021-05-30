<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;
use pocketmine\world\World;

class BorderCorrectionTask extends Task{
	/** @var MyPlot $plugin */
	protected $plugin;
	/** @var Plot $start */
	protected $start;
	/** @var World $level */
	protected $level;
	/** @var int $height */
	protected $height;
	/** @var Block $plotWallBlock */
	protected $plotWallBlock;
	/** @var Position|Vector3 $plotBeginPos */
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
	/** @var Plot $end */
	protected $end;
	/** @var bool $fillCorner */
	protected $fillCorner;
	/** @var int $cornerDirection */
	protected $cornerDirection;
	/** @var Vector3 $pos */
	protected $pos;
	/** @var int */
	protected $maxBlocksPerTick;

	public function __construct(MyPlot $plugin, Plot $start, Plot $end, bool $fillCorner = false, int $cornerDirection = -1, int $maxBlocksPerTick = 256) {
		$this->plugin = $plugin;
		$this->start = $start;
		$this->end = $end;
		$this->fillCorner = $fillCorner;
		$this->cornerDirection = $cornerDirection;
		$this->maxBlocksPerTick = $maxBlocksPerTick;

		$this->plotBeginPos = $plugin->getPlotPosition($start, false);
		$this->level = $this->plotBeginPos->getWorld();

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
			$this->direction = Facing::NORTH;
		}elseif(($start->X - $end->X) === -1) { // East X+
			$this->plotBeginPos = $this->plotBeginPos->add($plotSize, 0, 0);
			$this->xMax = (int) ($this->plotBeginPos->x + $roadWidth);
			$this->zMax = (int) ($this->plotBeginPos->z + $plotSize);
			$this->direction = Facing::EAST;
		}elseif(($start->Z - $end->Z) === -1) { // South Z+
			$this->plotBeginPos = $this->plotBeginPos->add(0, 0, $plotSize);
			$this->xMax = (int) ($this->plotBeginPos->x + $plotSize);
			$this->zMax = (int) ($this->plotBeginPos->z + $roadWidth);
			$this->direction = Facing::SOUTH;
		}elseif(($start->X - $end->X) === 1) { // West X-
			$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth, 0, 0);
			$this->xMax = (int) ($this->plotBeginPos->x + $roadWidth);
			$this->zMax = (int) ($this->plotBeginPos->z + $plotSize);
			$this->direction = Facing::WEST;
		}else{
			throw new \Exception('Merge Plots are not adjacent');
		}

		$this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);

		$plugin->getLogger()->debug("Border Correction Task started between plots {$start->X};{$start->Z} and {$end->X};{$end->Z}");
	}

	public function onRun() : void {
		$blocks = 0;
		if($this->direction === Facing::NORTH or $this->direction === Facing::SOUTH) {
			while($this->pos->z < $this->zMax) {
				while($this->pos->y < $this->level->getMaxY()) {
					if($this->pos->y > $this->height + 1)
						$block = BlockFactory::getInstance()->get(BlockLegacyIds::AIR, 0);
					elseif($this->pos->y === $this->height + 1){
						// TODO: change by x/z coord
						$block = $this->plotWallBlock;
					}elseif($this->pos->y === $this->height)
						$block = $this->roadBlock;
					elseif($this->pos->y === 0)
						$block = $this->bottomBlock;
					else//if($y < $this->height)
						$block = $this->groundBlock;

					$this->level->setBlock(new Vector3($this->pos->x - 1, $this->pos->y, $this->pos->z), $block, false);
					$this->level->setBlock(new Vector3($this->xMax, $this->pos->y, $this->pos->z), $block, false);
					$this->pos->y++;

					$blocks += 2;
					if($blocks >= $this->maxBlocksPerTick) {
						$this->setHandler(null);
						$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
						return;
					}
				}
				$this->pos->y = 0;
				$this->pos->z++;
			}
		}else{
			while($this->pos->x < $this->xMax) {
				while($this->pos->y < $this->level->getMaxY()) {
					if($this->pos->y > $this->height + 1)
						$block = BlockFactory::getInstance()->get(BlockLegacyIds::AIR, 0);
					elseif($this->pos->y === $this->height + 1)
						$block = $this->plotWallBlock; // TODO: change by x/z coord
					elseif($this->pos->y === $this->height)
						$block = $this->roadBlock;
					elseif($this->pos->y === 0)
						$block = $this->bottomBlock;
					else//if($y < $this->height)
						$block = $this->groundBlock;
					$this->level->setBlock(new Vector3($this->pos->x, $this->pos->y, $this->pos->z - 1), $block, false);
					$this->level->setBlock(new Vector3($this->pos->x, $this->pos->y, $this->zMax), $block, false);
					$this->pos->y++;
					$blocks += 2;
					if($blocks >= $this->maxBlocksPerTick) {
						$this->setHandler(null);
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