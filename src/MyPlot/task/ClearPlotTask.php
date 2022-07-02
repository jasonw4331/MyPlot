<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;
use pocketmine\world\World;

class ClearPlotTask extends Task {
	protected MyPlot $plugin;
	protected Plot $plot;
	protected World $level;
	protected int $height;
	protected Block $bottomBlock;
	protected Block $plotFillBlock;
	protected Block $plotFloorBlock;
	protected Position $plotBeginPos;
	protected int $xMax;
	protected int $zMax;
	protected int $maxBlocksPerTick;
	protected Vector3 $pos;
	protected ?AxisAlignedBB $plotBB;

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
		$plotLevel = $plugin->getLevelSettings($plot->levelName);
		$plotSize = $plotLevel->plotSize;
		$this->height = $plotLevel->groundHeight;
		$this->bottomBlock = $plotLevel->bottomBlock;
		$this->plotFillBlock = $plotLevel->plotFillBlock;
		$this->plotFloorBlock = $plotLevel->plotFloorBlock;
		$this->maxBlocksPerTick = $maxBlocksPerTick;

        $this->plotBeginPos = $plugin->getPlotPosition($plot, false);
        $this->xMax = (int)($this->plotBeginPos->x + $plotSize);
        $this->zMax = (int)($this->plotBeginPos->z + $plotSize);
		foreach ($plugin->getProvider()->getMergedPlots($plot) as $mergedPlot){
		    $xplot = $plugin->getPlotPosition($mergedPlot, false)->x;
		    $zplot = $plugin->getPlotPosition($mergedPlot, false)->z;
		    $xMaxPlot = (int)($xplot + $plotSize);
		    $zMaxPlot = (int)($zplot + $plotSize);
		    if($this->plotBeginPos->x > $xplot) $this->plotBeginPos->x = $xplot;
		    if($this->plotBeginPos->z > $zplot) $this->plotBeginPos->z = $zplot;
		    if($this->xMax < $xMaxPlot) $this->xMax = $xMaxPlot;
		    if($this->zMax < $zMaxPlot) $this->zMax = $zMaxPlot;
        }
        $this->level = $this->plotBeginPos->getWorld();
        $this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);
        $this->plotBB = $this->plugin->getPlotBB($plot);
		$plugin->getLogger()->debug("Plot Clear Task started at plot $plot->X;$plot->Z");
	}

	public function onRun() : void {
		foreach($this->level->getEntities() as $entity) {
			if($this->plotBB->isVectorInXZ($entity->getPosition())) {
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
				while($this->pos->y < $this->level->getMaxY()) {
					if($this->pos->y === 0) {
						$block = $this->bottomBlock;
					}elseif($this->pos->y < $this->height) {
						$block = $this->plotFillBlock;
					}elseif($this->pos->y === $this->height) {
						$block = $this->plotFloorBlock;
					}else{
						$block = VanillaBlocks::AIR();
					}
					$this->level->setBlock($this->pos, $block, false);
					$blocks++;
					if($blocks >= $this->maxBlocksPerTick) {
						$this->setHandler(null);
						$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
						throw new CancelTaskException();
					}
					$this->pos->y++;
				}
				$this->pos->y = 0;
				$this->pos->z++;
			}
			$this->pos->z = $this->plotBeginPos->z;
			$this->pos->x++;
		}

		foreach($this->plugin->getPlotChunks($this->plot) as [$chunkX, $chunkZ, $chunk]) {
			if($chunk === null)
				continue;
			foreach($chunk->getTiles() as $tile) {
				$tile->close();
			}
		}
		$this->plugin->getScheduler()->scheduleDelayedTask(new ClearBorderTask($this->plugin, $this->plot), 1);
		$this->plugin->getLogger()->debug("Plot Clear task completed at {$this->plotBeginPos->x};{$this->plotBeginPos->z}");
	}
}