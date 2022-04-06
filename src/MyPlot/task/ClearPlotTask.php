<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use MyPlot\PlotLevelSettings;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class ClearPlotTask extends Task{
	protected AxisAlignedBB $aabb;
	protected Vector3 $plotBeginPos;
	protected PlotLevelSettings $plotLevel;
	protected Vector3 $pos;
	protected World $world;

	public function __construct(private MyPlot $plugin, private BasePlot $plot, private int $maxBlocksPerTick = 256){
		$this->world = $this->plugin->getServer()->getWorldManager()->getWorldByName($plot->levelName);
		$this->plotLevel = $plugin->getLevelSettings($plot->levelName);
		$this->aabb = $aabb = $this->plugin->getPlotBB($plot);
		$this->plotBeginPos = $this->pos = new Vector3(
			$aabb->minX,
			$aabb->minY,
			$aabb->minZ
		);
		$plugin->getLogger()->debug("Plot Clear Task started at plot $plot->X;$plot->Z");
	}

	public function onRun() : void{
		foreach($this->world->getEntities() as $entity){
			if($this->aabb->isVectorInXZ($entity->getPosition())){
				if(!$entity instanceof Player){
					$entity->flagForDespawn();
				}else{
					$this->plugin->teleportPlayerToPlot($entity, $this->plot);
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
		foreach($this->plugin->getPlotChunks($this->plot) as [$chunkX, $chunkZ, $chunk]){
			if($chunk === null)
				continue;
			foreach($chunk->getTiles() as $tile)
				if($this->aabb->isVectorInXZ($tile->getPosition()))
					$tile->close();
		}
		$this->plugin->getScheduler()->scheduleDelayedTask(new ClearBorderTask($this->plugin, $this->plot, $this->maxBlocksPerTick), 1);
		$this->plugin->getLogger()->debug("Plot Clear Task completed at {$this->plot->X};{$this->plot->Z}");
	}
}