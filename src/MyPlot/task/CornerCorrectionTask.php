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
use pocketmine\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class CornerCorrectionTask extends Task{
	protected AxisAlignedBB $aabb;
	protected Vector3 $plotBeginPos;
	protected PlotLevelSettings $plotLevel;
	protected Vector3 $pos;
	protected World $world;

	public function __construct(private MyPlot $plugin, private BasePlot $start, private BasePlot $end, private int $cornerDirection, private int $maxBlocksPerTick = 256){
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
			if($cornerDirection === Facing::EAST){
				$this->plotBeginPos = $this->plotBeginPos->subtract(0, 0, $roadWidth);
				$this->plotBeginPos = $this->plotBeginPos->add($plotSize, 0, 0);
			}elseif($cornerDirection === Facing::WEST){
				$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth, 0, $roadWidth);
			}
		}elseif(($start->X - $end->X) === -1){ // East X+
			if($cornerDirection === Facing::NORTH){
				$this->plotBeginPos = $this->plotBeginPos->add($plotSize, 0, 0);
				$this->plotBeginPos = $this->plotBeginPos->subtract(0, 0, $roadWidth);
			}elseif($cornerDirection === Facing::SOUTH){
				$this->plotBeginPos = $this->plotBeginPos->add($plotSize, 0, $plotSize);
			}
		}elseif(($start->Z - $end->Z) === -1){ // South Z+
			if($cornerDirection === Facing::EAST){
				$this->plotBeginPos = $this->plotBeginPos->add($plotSize, 0, $plotSize);
				$this->plotBeginPos = $this->plotBeginPos->add($plotSize, 0, 0);
			}elseif($cornerDirection === Facing::WEST){
				$this->plotBeginPos = $this->plotBeginPos->add(0, 0, $plotSize);
				$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth, 0, 0);
			}
		}elseif(($start->X - $end->X) === 1){ // West X-
			if($cornerDirection === Facing::NORTH){
				$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth, 0, $roadWidth);
			}elseif($cornerDirection === Facing::SOUTH){
				$this->plotBeginPos = $this->plotBeginPos->add(0, 0, $plotSize);
				$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth, 0, 0);
			}
		}
		$this->xMax = (int) ($this->plotBeginPos->x + $roadWidth);
		$this->zMax = (int) ($this->plotBeginPos->z + $roadWidth);
		$this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);
		$plugin->getLogger()->debug("Corner Correction Task started between plots $start->X;$start->Z and $end->X;$end->Z");
	}

	public function onRun() : void{
		foreach($this->level->getEntities() as $entity){
			if($entity->getPosition()->x > $this->pos->x - 1 and $entity->getPosition()->x < $this->xMax + 1){
				if($entity->getPosition()->z > $this->pos->z - 1 and $entity->getPosition()->z < $this->zMax + 1){
					if(!$entity instanceof Player){
						$entity->flagForDespawn();
					}else{
						$this->plugin->teleportPlayerToPlot($entity, $this->start);
					}
				}
			}
		}
		$blocks = 0;
		while($this->pos->x < $this->xMax){
			while($this->pos->z < $this->zMax){
				while($this->pos->y < $this->level->getMaxY()){
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
					if($blocks >= $this->maxBlocksPerTick){
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

		$this->plugin->getLogger()->debug("Corner Correction Task completed");
	}
}