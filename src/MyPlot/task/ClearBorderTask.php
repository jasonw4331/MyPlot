<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use MyPlot\PlotLevelSettings;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class ClearBorderTask extends Task{
	protected AxisAlignedBB $aabb;
	protected Vector3 $plotBeginPos;
	protected PlotLevelSettings $plotLevel;
	protected Vector3 $pos;
	protected World $world;

	public function __construct(private MyPlot $plugin, private BasePlot $plot, private int $maxBlocksPerTick = 256){
		$this->world = $this->plugin->getServer()->getWorldManager()->getWorldByName($plot->levelName);
		$this->plotLevel = $plugin->getLevelSettings($plot->levelName);
		$this->aabb = $aabb = $this->plugin->getPlotBB($plot)->expand(1, 0, 1);
		$this->plotBeginPos = $this->pos = new Vector3(
			$aabb->minX,
			$aabb->minY,
			$aabb->minZ
		);
		$plugin->getLogger()->debug("Border Clear Task started at plot $plot->X;$plot->Z");
	}

	public function onRun() : void{
		$blocks = 0;
		for($x = $this->plotBeginPos->x; $x <= $this->aabb->maxX; $x++){
			for($y = 0; $y < $this->aabb->maxY; ++$y){
				if($y > $this->plotLevel->groundHeight + 1)
					$block = VanillaBlocks::AIR();
				elseif($y === $this->plotLevel->groundHeight + 1)
					$block = $this->plotLevel->wallBlock;
				elseif($y === $this->plotLevel->groundHeight)
					$block = $this->plotLevel->roadBlock;
				elseif($y === $this->aabb->minY)
					$block = $this->plotLevel->bottomBlock;
				else//if($y < $this->plotLevel->groundHeight)
					$block = $this->plotLevel->plotFloorBlock;
				$this->world->setBlock(new Vector3($x, $y, $this->plotBeginPos->z), $block, false);
				$this->world->setBlock(new Vector3($x, $y, $this->aabb->maxZ), $block, false);
				$blocks += 2;
			}
		}
		if($blocks >= $this->maxBlocksPerTick){
			$this->setHandler(null);
			$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
			throw new CancelTaskException();
		}
		for($z = $this->plotBeginPos->z; $z <= $this->aabb->maxZ; $z++){
			for($y = 0; $y < $this->aabb->maxY; ++$y){
				if($y > $this->plotLevel->groundHeight + 1)
					$block = VanillaBlocks::AIR();
				elseif($y === $this->plotLevel->groundHeight + 1)
					$block = $this->plotLevel->wallBlock;
				elseif($y === $this->plotLevel->groundHeight)
					$block = $this->plotLevel->roadBlock;
				elseif($y === $this->aabb->minY)
					$block = $this->plotLevel->bottomBlock;
				else//if($y < $this->plotLevel->groundHeight)
					$block = $this->plotLevel->plotFloorBlock;
				$this->world->setBlock(new Vector3($this->plotBeginPos->x, $y, $z), $block, false);
				$this->world->setBlock(new Vector3($this->aabb->maxX, $y, $z), $block, false);
				$blocks += 2;
			}
		}
		if($blocks >= $this->maxBlocksPerTick){
			$this->setHandler(null);
			$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
			throw new CancelTaskException();
		}
		$this->plugin->getLogger()->debug("Border Clear Task completed at {$this->plot->X};{$this->plot->Z}");
	}
}