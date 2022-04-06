<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\plot\MergedPlot;
use MyPlot\PlotLevelSettings;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

class RoadFillTask extends Task{
	public const LOW_BORDER = 0;
	public const HIGH_BORDER = 1;
	public const BOTH_BORDERS = 2;

	protected AxisAlignedBB $aabb;
	protected Vector3 $plotBeginPos;
	protected PlotLevelSettings $plotLevel;
	protected Vector3 $pos;
	protected World $world;

	public function __construct(private MyPlot $plugin, private MergedPlot $start, private int $direction, private int $handleBorder = self::BOTH_BORDERS, private int $maxBlocksPerTick = 256){
		$this->world = $this->plugin->getServer()->getWorldManager()->getWorldByName($start->levelName);
		$this->plotLevel = $plotLevel = $plugin->getLevelSettings($start->levelName);
		$plotSize = $plotLevel->plotSize;
		$totalSize = $plotSize + $plotLevel->roadWidth;
		$aabb = $plugin->getPlotBB($start);
		$this->aabb = $aabb = match ($direction) {
			Facing::NORTH => new AxisAlignedBB(
				$aabb->minX,
				$aabb->minY,
				$aabb->minZ - $plotLevel->roadWidth,
				$aabb->maxX,
				$aabb->maxY,
				$aabb->minZ
			),
			Facing::EAST => new AxisAlignedBB(
				$aabb->maxX + ($totalSize * ($this->start->xWidth - 1)),
				$aabb->minY,
				$aabb->minZ,
				$aabb->maxX + ($totalSize * ($this->start->xWidth - 1)) + $plotLevel->roadWidth,
				$aabb->maxY,
				$aabb->maxZ
			),
			Facing::SOUTH => new AxisAlignedBB(
				$aabb->minX,
				$aabb->minY,
				$aabb->maxZ + ($totalSize * ($this->start->zWidth - 1)),
				$aabb->maxX,
				$aabb->maxY,
				$aabb->maxZ + ($totalSize * ($this->start->zWidth - 1)) + $plotLevel->roadWidth
			),
			Facing::WEST => new AxisAlignedBB(
				$aabb->minX - $plotLevel->roadWidth,
				$aabb->minY,
				$aabb->minZ,
				$aabb->minX,
				$aabb->maxY,
				$aabb->maxZ
			),
			default => throw new \InvalidArgumentException("Invalid direction $direction")
		};
		$this->plotBeginPos = $this->pos = new Vector3(
			$aabb->minX,
			$aabb->minY,
			$aabb->minZ
		);

		$plugin->getLogger()->debug("Road Clear Task started between plots $start->X;$start->Z facing" . Facing::toString($direction));
	}

	public function onRun() : void{
		foreach($this->world->getEntities() as $entity){
			if($this->aabb->isVectorInXZ($entity->getPosition())){
				if(!$entity instanceof Player){
					$entity->flagForDespawn();
				}else{
					$this->plugin->teleportPlayerToPlot($entity, $this->start);
				}
			}
		}
		$blocks = 0;
		for(; $this->pos->x < $this->aabb->maxX; ++$this->pos->x, $this->pos->z = $this->aabb->minZ){
			for(; $this->pos->z < $this->aabb->maxZ; ++$this->pos->z, $this->pos->y = $this->aabb->minY){
				for(; $this->pos->y < $this->aabb->maxY; ++$this->pos->y){
					if($this->pos->y === $this->aabb->minY)
						$block = $this->plotLevel->bottomBlock;
					elseif($this->pos->y < $this->plotLevel->groundHeight)
						$block = $this->plotLevel->plotFillBlock;
					elseif($this->pos->y === $this->plotLevel->groundHeight)
						$block = $this->plotLevel->plotFloorBlock;
					elseif($this->pos->y === $this->plotLevel->groundHeight + 1 and
						(
							(
								Facing::axis($this->direction) === Axis::X and
								(
									(
										(
											$this->handleBorder === self::LOW_BORDER or
											$this->handleBorder === self::BOTH_BORDERS
										) and
										$this->pos->z === $this->aabb->maxZ
									) or (
										(
											$this->handleBorder === self::HIGH_BORDER or
											$this->handleBorder === self::BOTH_BORDERS
										) and
										$this->pos->z === $this->aabb->minZ
									)
								)
							) or (
								Facing::axis($this->direction) === Axis::Z and
								(
									(
										(
											$this->handleBorder === self::LOW_BORDER or
											$this->handleBorder === self::BOTH_BORDERS
										) and
										$this->pos->x === $this->aabb->maxX
									) or (
										(
											$this->handleBorder === self::HIGH_BORDER or
											$this->handleBorder === self::BOTH_BORDERS
										) and
										$this->pos->x === $this->aabb->minX
									)
								)
							)
						)
					)
						$block = $this->plotLevel->wallBlock;
					else
						$block = VanillaBlocks::AIR();
					$this->world->setBlock($this->pos, $block, false);

					if(++$blocks >= $this->maxBlocksPerTick){
						$this->setHandler(null);
						$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
						throw new CancelTaskException();
					}
				}
			}
		}

		$aabb = $this->aabb;
		$xMin = $aabb->minX >> Chunk::COORD_MASK;
		$zMin = $aabb->minZ >> Chunk::COORD_MASK;
		$xMax = $aabb->maxX >> Chunk::COORD_MASK;
		$zMax = $aabb->maxZ >> Chunk::COORD_MASK;

		$level = $this->plugin->getServer()->getWorldManager()->getWorldByName($this->start->levelName);
		for($x = $xMin; $x <= $xMax; ++$x){
			for($z = $zMin; $z <= $zMax; ++$z){
				$chunk = $level->getChunk($x, $z);
				if($chunk === null)
					continue;
				foreach($chunk->getTiles() as $tile)
					if($aabb->isVectorInXZ($tile->getPosition()))
						$tile->close();
			}
		}
		$this->plugin->getLogger()->debug("Plot Road Clear task completed at {$this->start->X};{$this->start->Z}");
	}
}