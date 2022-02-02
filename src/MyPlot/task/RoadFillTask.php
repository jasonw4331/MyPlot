<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class RoadFillTask extends Task{
	protected MyPlot $plugin;
	protected Plot $start;
	protected Plot $end;
	protected World $level;
	protected int $height;
	protected ?Vector3 $plotBeginPos;
	protected int $xMax;
	protected int $zMax;
	protected Block $roadBlock;
	protected Block $groundBlock;
	protected Block $bottomBlock;
	protected int $maxBlocksPerTick;
	protected Vector3 $pos;
	protected bool $fillCorner;
	protected int $cornerDirection = -1;

	public function __construct(MyPlot $plugin, Plot $start, Plot $end, bool $fillCorner = false, int $cornerDirection = -1, int $maxBlocksPerTick = 256) {
		if($start->isSame($end))
			throw new \Exception("Plot arguments cannot be the same plot or already be merged");

		$this->plugin = $plugin;
		$this->start = $start;
		$this->end = $end;
		$this->fillCorner = $fillCorner;
		$this->cornerDirection = $cornerDirection === -1 ? -1 : Facing::opposite($cornerDirection);

		$this->plotBeginPos = $plugin->getPlotPosition($start, false);
		$this->level = $this->plotBeginPos->getWorld();

		$plotLevel = $plugin->getLevelSettings($start->levelName);
		$plotSize = $plotLevel->plotSize;
		$roadWidth = $plotLevel->roadWidth;
		$this->height = $plotLevel->groundHeight;
		$this->roadBlock = $plotLevel->plotFloorBlock;
		$this->groundBlock = $plotLevel->plotFillBlock;
		$this->bottomBlock = $plotLevel->bottomBlock;

		if(($start->Z - $end->Z) === 1){ // North Z-
			$this->plotBeginPos = $this->plotBeginPos->subtract(0, 0, $roadWidth);
			$this->xMax = (int) ($this->plotBeginPos->x + $plotSize);
			$this->zMax = (int) ($this->plotBeginPos->z + $roadWidth);
		}elseif(($start->X - $end->X) === -1){ // East X+
			$this->plotBeginPos = $this->plotBeginPos->add($plotSize, 0, 0);
			$this->xMax = (int) ($this->plotBeginPos->x + $roadWidth);
			$this->zMax = (int) ($this->plotBeginPos->z + $plotSize);
		}elseif(($start->Z - $end->Z) === -1){ // South Z+
			$this->plotBeginPos = $this->plotBeginPos->add(0, 0, $plotSize);
			$this->xMax = (int) ($this->plotBeginPos->x + $plotSize);
			$this->zMax = (int) ($this->plotBeginPos->z + $roadWidth);
		}elseif(($start->X - $end->X) === 1){ // West X-
			$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth, 0, 0);
			$this->xMax = (int) ($this->plotBeginPos->x + $roadWidth);
			$this->zMax = (int) ($this->plotBeginPos->z + $plotSize);
		}

		$this->maxBlocksPerTick = $maxBlocksPerTick;
		$this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);

		$plugin->getLogger()->debug("Road Clear Task started between plots $start->X;$start->Z and $end->X;$end->Z");
	}

	public function onRun() : void {
		foreach($this->level->getEntities() as $entity) {
			if($entity->getPosition()->x > $this->pos->x - 1 and $entity->getPosition()->x < $this->xMax + 1) {
				if($entity->getPosition()->z > $this->pos->z - 1 and $entity->getPosition()->z < $this->zMax + 1) {
					if(!$entity instanceof Player){
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
				while($this->pos->y < $this->level->getMaxY()) {
					if($this->pos->y === 0)
						$block = $this->bottomBlock;
					elseif($this->pos->y < $this->height)
						$block = $this->groundBlock;
					elseif($this->pos->y === $this->height)
						$block = $this->roadBlock;
					else
						$block = VanillaBlocks::AIR();

					$this->level->setBlock($this->pos, $block, false);
					$this->pos->y++;

					$blocks++;
					if($blocks >= $this->maxBlocksPerTick) {
						$this->setHandler(null);
						$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
						throw new CancelTaskException();
					}
				}
				$this->pos->y = 0;
				$this->pos->z++;
			}
			$this->pos->z = $this->plotBeginPos->z;
			$this->pos->x++;
		}
		$this->plugin->getLogger()->debug("Plot Road Clear task completed at {$this->start->X};{$this->start->Z}");

		$this->plugin->getScheduler()->scheduleTask(new BorderCorrectionTask($this->plugin, $this->start, $this->end, $this->fillCorner, $this->cornerDirection));
	}
}