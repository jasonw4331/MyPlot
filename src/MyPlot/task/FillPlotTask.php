<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;
use pocketmine\world\World;

class FillPlotTask extends Task {
	/** @var MyPlot $plugin */
	protected $plugin;
	/** @var Plot $plot */
	protected $plot;
	/** @var World|null $level */
	protected $level;
	/** @var int $height */
	protected $height;
	/** @var Block $fillBlock */
	protected $fillBlock;
	/** @var Block $bottomBlock */
	protected $bottomBlock;
	/** @var Position|null $plotBeginPos */
	protected $plotBeginPos;
	/** @var int $xMax */
	protected $xMax;
	/** @var int $zMax */
	protected $zMax;
	/** @var int $maxBlocksPerTick */
	protected $maxBlocksPerTick;
	/** @var Vector3 $pos */
	protected $pos;

	/**
	 * FillPlotTask constructor.
	 *
	 * @param MyPlot $plugin
	 * @param Plot $plot
	 * @param int $maxBlocksPerTick
	 */
	public function __construct(MyPlot $plugin, Plot $plot, Block $plotFillBlock, int $maxBlocksPerTick = 256) {
		$this->plugin = $plugin;
		$this->plot = $plot;
		$this->plotBeginPos = $plugin->getPlotPosition($plot);
		$this->level = $this->plotBeginPos->getWorld();
		$plotLevel = $plugin->getLevelSettings($plot->levelName);
		$plotSize = $plotLevel->plotSize;
		$this->xMax = (int)($this->plotBeginPos->x + $plotSize);
		$this->zMax = (int)($this->plotBeginPos->z + $plotSize);
		$this->height = $plotLevel->groundHeight;
		$this->fillBlock = $plotFillBlock;
		$this->bottomBlock = $plotLevel->bottomBlock;
		$this->maxBlocksPerTick = $maxBlocksPerTick;
		$this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);
		$plugin->getLogger()->debug("Plot Fill Task started at plot $plot->X;$plot->Z");
	}

	public function onRun() : void {
		foreach($this->level->getEntities() as $entity) {
			if($this->plugin->getPlotBB($this->plot)->isVectorInXZ($entity->getPosition())) {
				if(!$entity instanceof Player) {
					$entity->flagForDespawn();
				}else {
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
					}elseif($this->pos->y <= $this->height) {
						$block = $this->fillBlock;
					}else {
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
			foreach($chunk->getTiles() as $tile) {
				$tile->close();
			}
		}
		$this->plugin->getLogger()->debug("Plot Fill task completed at {$this->plotBeginPos->x};{$this->plotBeginPos->z}");
	}
}