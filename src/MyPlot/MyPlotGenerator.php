<?php
namespace MyPlot;

use pocketmine\block\Block;
use pocketmine\level\format\Chunk as FullChunk;
use pocketmine\level\ChunkManager;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class MyPlotGenerator extends GeneratorTemplate
{

	public static $name = "MyPlotGenerator";

    /** @var Block */
    public $roadBlock, $wallBlock, $plotFloorBlock, $plotFillBlock, $bottomBlock;

    /** @var int */
    public $roadWidth, $plotSize, $groundHeight;

    const PLOT = 0;
    const ROAD = 1;
    const WALL = 2;

    public function __construct(array $settings = []) {
        parent::__construct($settings);
        $this->roadBlock = $this->parseBlock($settings, "RoadBlock", new Block(5));
        $this->wallBlock = $this->parseBlock($settings, "WallBlock", new Block(44));
        $this->plotFloorBlock = $this->parseBlock($settings, "PlotFloorBlock", new Block(2));
        $this->plotFillBlock = $this->parseBlock($settings, "PlotFillBlock", new Block(3));
        $this->bottomBlock = $this->parseBlock($settings, "BottomBlock", new Block(7));
        $this->roadWidth = $this->parseNumber($settings, "RoadWidth", 7);
        $this->plotSize = $this->parseNumber($settings, "PlotSize", 32);
        $this->groundHeight = $this->parseNumber($settings, "GroundHeight", 64);

        $this->settings["preset"] = json_encode([
            "RoadBlock" => $this->roadBlock->getId() . (($meta = $this->roadBlock->getDamage()) === 0 ? '' : ':'.$meta),
            "WallBlock" => $this->wallBlock->getId() . (($meta = $this->wallBlock->getDamage()) === 0 ? '' : ':'.$meta),
            "PlotFloorBlock" => $this->plotFloorBlock->getId() . (($meta = $this->plotFloorBlock->getDamage()) === 0 ? '' : ':'.$meta),
            "PlotFillBlock" => $this->plotFillBlock->getId() . (($meta =$this->plotFillBlock->getDamage()) === 0 ? '' : ':'.$meta),
            "BottomBlock" => $this->bottomBlock->getId() . (($meta = $this->bottomBlock->getDamage()) === 0 ? '' : ':'.$meta),
            "RoadWidth" => $this->roadWidth,
            "PlotSize" => $this->plotSize,
            "GroundHeight" => $this->groundHeight,
        ]);
    }

    public function getName() {
        return self::$name;
    }

    public function getSettings() {
        return $this->settings;
    }

    public final function init(ChunkManager $level, Random $random) {
        $this->level = $level;
    }

    public final function generateChunk($chunkX, $chunkZ) {
        $shape = $this->getShape($chunkX << 4, $chunkZ << 4);
	    /** @var FullChunk $chunk */
	    $chunk = $this->level->getChunk($chunkX, $chunkZ);
	    if(!$chunk instanceof FullChunk) {
		    return;
	    }
        $chunk->setGenerated();
        $bottomBlockId = $this->bottomBlock->getId();
        $bottomBlockMeta = $this->bottomBlock->getDamage();
        $plotFillBlockId = $this->plotFillBlock->getId();
        $plotFillBlockMeta = $this->plotFillBlock->getDamage();
        $plotFloorBlockId = $this->plotFloorBlock->getId();
        $plotFloorBlockMeta = $this->plotFloorBlock->getDamage();
        $roadBlockId = $this->roadBlock->getId();
        $roadBlockMeta = $this->roadBlock->getDamage();
        $wallBlockId = $this->wallBlock->getId();
        $wallBlockMeta = $this->wallBlock->getDamage();
        $groundHeight = $this->groundHeight;

        for ($Z = 0; $Z < 16; ++$Z) {
            for ($X = 0; $X < 16; ++$X) {
                $chunk->setBiomeId($X, $Z, 1);
                $chunk->setBlock($X, 0, $Z, $bottomBlockId, $bottomBlockMeta);
                for ($y = 1; $y < $groundHeight; ++$y) {
                    $chunk->setBlock($X, $y, $Z, $plotFillBlockId, $plotFillBlockMeta);
                }
                $type = $shape[($Z << 4) | $X];
                if ($type === self::PLOT) {
                    $chunk->setBlock($X, $groundHeight, $Z, $plotFloorBlockId, $plotFloorBlockMeta);
                } elseif ($type === self::ROAD) {
                    $chunk->setBlock($X, $groundHeight, $Z, $roadBlockId, $roadBlockMeta);
                } else {
                    $chunk->setBlock($X, $groundHeight, $Z, $roadBlockId, $roadBlockMeta);
                    $chunk->setBlock($X, $groundHeight + 1, $Z, $wallBlockId, $wallBlockMeta);
                }
            }
        }
        $chunk->setX($chunkX);
        $chunk->setZ($chunkZ);
        $this->level->setChunk($chunkX, $chunkZ, $chunk);
    }

    public final function getShape($x, $z) {
        $totalSize = $this->plotSize + $this->roadWidth;

        if ($x >= 0) {
            $X = $x % $totalSize;
        } else {
            $X = $totalSize - abs($x % $totalSize);
        }
        if ($z >= 0) {
            $Z = $z % $totalSize;
        } else {
            $Z = $totalSize - abs($z % $totalSize);
        }

        $startX = $X;
        $shape = new \SplFixedArray(256);

        for ($z = 0; $z < 16; $z++, $Z++) {
            if ($Z === $totalSize) {
                $Z = 0;
            }
            if ($Z < $this->plotSize) {
                $typeZ = self::PLOT;
            } elseif ($Z === $this->plotSize or $Z === ($totalSize-1)) {
                $typeZ = self::WALL;
            } else {
                $typeZ = self::ROAD;
            }

            for ($x = 0, $X = $startX; $x < 16; $x++, $X++) {
                if ($X === $totalSize)
                    $X = 0;
                if ($X < $this->plotSize) {
                    $typeX = self::PLOT;
                } elseif ($X === $this->plotSize or $X === ($totalSize-1)) {
                    $typeX = self::WALL;
                } else {
                    $typeX = self::ROAD;
                }
                if ($typeX === $typeZ) {
                    $type = $typeX;
                } elseif ($typeX === self::PLOT) {
                    $type = $typeZ;
                } elseif ($typeZ === self::PLOT) {
                    $type = $typeX;
                } else {
                    $type = self::ROAD;
                }
                $shape[($z << 4)| $x] = $type;
            }
        }
        return $shape;
    }

    public final function populateChunk($chunkX, $chunkZ) {}

    public final function getSpawn() {
        return new Vector3(0, $this->groundHeight, 0);
    }
}