<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use MyPlot\PlotLevelSettings;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class BorderCorrectionTask extends Task{
	protected AxisAlignedBB $aabb;
	protected Vector3 $plotBeginPos;
	protected PlotLevelSettings $plotLevel;
	protected Vector3 $pos;
	protected World $world;

	public function __construct(private MyPlot $plugin, private BasePlot $start, private BasePlot $end, private bool $fillCorner = false, private int $cornerDirection = -1, private int $maxBlocksPerTick = 256){
		if($start->isSame($end))
			throw new \Exception("Plot arguments cannot be the same plot or already be merged");
		if(abs($start->X - $end->X) !== 1 and abs($start->Z - $end->Z) !== 1)
			throw new \Exception("Plot arguments must be adjacent plots");

		$this->world = $this->plugin->getServer()->getWorldManager()->getWorldByName($start->levelName);
		$this->plotLevel = $plugin->getLevelSettings($start->levelName);
		$this->aabb = $aabb = null;
		$this->plotBeginPos = $this->pos = new Vector3(
			$aabb->minX,
			$aabb->minY,
			$aabb->minZ
		);

		if(($start->Z - $end->Z) === 1){ // North Z-
			$this->plotBeginPos = $this->plotBeginPos->subtract(0, 0, $roadWidth);
			$this->xMax = (int) ($this->plotBeginPos->x + $plotSize);
			$this->zMax = (int) ($this->plotBeginPos->z + $roadWidth);
			$this->direction = Facing::NORTH;
		}elseif(($start->X - $end->X) === -1){ // East X+
			$this->plotBeginPos = $this->plotBeginPos->add($plotSize, 0, 0);
			$this->xMax = (int) ($this->plotBeginPos->x + $roadWidth);
			$this->zMax = (int) ($this->plotBeginPos->z + $plotSize);
			$this->direction = Facing::EAST;
		}elseif(($start->Z - $end->Z) === -1){ // South Z+
			$this->plotBeginPos = $this->plotBeginPos->add(0, 0, $plotSize);
			$this->xMax = (int) ($this->plotBeginPos->x + $plotSize);
			$this->zMax = (int) ($this->plotBeginPos->z + $roadWidth);
			$this->direction = Facing::SOUTH;
		}elseif(($start->X - $end->X) === 1){ // West X-
			$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth, 0, 0);
			$this->xMax = (int) ($this->plotBeginPos->x + $roadWidth);
			$this->zMax = (int) ($this->plotBeginPos->z + $plotSize);
			$this->direction = Facing::WEST;
		}else{
			throw new \Exception('Merge Plots are not adjacent');
		}

		$this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);

		$plugin->getLogger()->debug("Border Correction Task started between plots $start->X;$start->Z and $end->X;$end->Z");
	}

	public function onRun() : void{
		$blocks = 0;
		if($this->direction === Facing::NORTH or $this->direction === Facing::SOUTH){
			while($this->pos->z < $this->zMax){
				while($this->pos->y < $this->level->getMaxY()){
					if($this->pos->y > $this->height + 1)
						$block = VanillaBlocks::AIR();
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
					if($blocks >= $this->maxBlocksPerTick){
						$this->setHandler(null);
						$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
						throw new CancelTaskException();
					}
				}
				$this->pos->y = 0;
				$this->pos->z++;
			}
		}else{
			while($this->pos->x < $this->xMax){
				while($this->pos->y < $this->level->getMaxY()){
					if($this->pos->y > $this->height + 1)
						$block = VanillaBlocks::AIR();
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
					if($blocks >= $this->maxBlocksPerTick){
						$this->setHandler(null);
						$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
						throw new CancelTaskException();
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