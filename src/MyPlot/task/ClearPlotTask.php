<?php
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat;

class ClearPlotTask extends PluginTask
{
    private $level, $height, $bottomBlock, $plotFillBlock, $plotFloorBlock,
            $plotBeginPos, $xMax, $zMax, $maxBlocksPerTick, $issuer;

    public function __construct(MyPlot $plugin, Plot $plot, Player $issuer = null, $maxBlocksPerTick = 256) {
        parent::__construct($plugin);
        $this->plotBeginPos = $plugin->getPlotPosition($plot);
        $this->level = $this->plotBeginPos->getLevel();

        $plotLevel = $plugin->getLevelSettings($plot->levelName);

        $plotSize = $plotLevel->plotSize;
        $this->xMax = $this->plotBeginPos->x + $plotSize;
        $this->zMax = $this->plotBeginPos->z + $plotSize;

        $this->height = $plotLevel->groundHeight;
        $this->bottomBlock = $plotLevel->bottomBlock;
        $this->plotFillBlock = $plotLevel->plotFillBlock;
        $this->plotFloorBlock = $plotLevel->plotFloorBlock;

        $this->maxBlocksPerTick = $maxBlocksPerTick;
        $this->issuer = $issuer;

        $this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);
    }

    public function onRun($tick) {
        $blocks = 0;
        while ($this->pos->x < $this->xMax) {
            while ($this->pos->z < $this->zMax) {
                while ($this->pos->y < 128) {
                    if ($this->pos->y === 0) {
                        $block = $this->bottomBlock;
                    } elseif ($this->pos->y < $this->height) {
                        $block = $this->plotFillBlock;
                    } elseif ($this->pos->y === $this->height) {
                        $block = $this->plotFloorBlock;
                    } else {
                        $block = Block::get(0);
                    }
                    $this->level->setBlock($this->pos, $block, false, false);
                    $blocks++;
                    if ($blocks === $this->maxBlocksPerTick) {
                        $this->getOwner()->getServer()->getScheduler()->scheduleDelayedTask($this, 1);
                        return;
                    }
                    $this->pos->y++;
                }
                $this->pos->y = 0;
                $this->pos->z++;
            }
            $this->pos->z = $this->plotBeginPos->z;
            $this->pos->x++;
        }
        if ($this->issuer !== null) {
            $this->issuer->sendMessage(TextFormat::GREEN . "Successfully cleared this plot");
        }
    }
}