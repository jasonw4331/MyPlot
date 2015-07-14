<?php
namespace MyPlot;

use pocketmine\block\Block;
use pocketmine\level\generator\Generator;
use pocketmine\level\ChunkManager;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\level\generator\biome\Biome;

class MyPlotGenerator extends Generator
{
    private $level;
    private $settings = array();
    public $roadBlock, $wallBlock, $plotFloorBlock, $plotFillBlock, $bottomBlock;
    public $roadWidth, $plotSize, $groundHeight;

    public function __construct(array $settings = array()) {
        $defaultBlocks = array (
            "RoadBlock" => new Block(5),
            "WallBlock" => new Block(44),
            "PlotFloorBlock" => new Block(2),
            "PlotFillBlock" => new Block(3),
            "BottomBlock" => new Block(7),
        );
        foreach($defaultBlocks as $key => $defaultBlock) {
            if (isset($settings[$key])) {
                $blockStr = $settings[$key];
                if (is_numeric($blockStr)) {
                    $block = new Block($blockStr);
                } else {
                    $split = explode(":", $blockStr);
                    if (count($split) === 2 and is_numeric($split[0]) and is_numeric($split[1])) {
                        $block = new Block($split[0], $split[1]);
                    } else {
                        $block = $defaultBlock;
                    }
                }
            } else {
                $block = $defaultBlock;
            }
            $this->{lcfirst($key)} = $block;
        }

        $defaultNumbers = array(
            "RoadWidth" => 7,
            "PlotSize" => 22,
            "GroundHeight" => 64,
        );
        foreach ($defaultNumbers as $key => $defaultNumber) {
            if (isset($settings[$key]) and is_numeric($settings[$key])) {
                $number = $settings[$key];
            } else {
                $number = $defaultNumber;
            }
            $this->{lcfirst($key)} = $number;
        }
    }

    public function getName() {
        return "myplot";
    }

    public function getSettings() {
        return $this->settings;
    }

    public function init(ChunkManager $level, Random $random) {
        $this->level = $level;
    }

    public function generateChunk($chunkX, $chunkZ) {
        $shape = $this->getShape($chunkX*16, $chunkZ*16);
        $chunk = $this->level->getChunk($chunkX, $chunkZ);
        $chunk->setGenerated();
        $c = Biome::getBiome(1)->getColor();
        $R = $c >> 16;
        $G = ($c >> 8) & 0xff;
        $B = $c & 0xff;

        for ($Z = 0; $Z < 16; ++$Z) {
            for ($X = 0; $X < 16; ++$X) {
                $chunk->setBiomeId($X, $Z, 1);
                $chunk->setBiomeColor($X, $Z, $R, $G, $B);

                $chunk->setBlock($X, 0, $Z, $this->bottomBlock->getId(), $this->bottomBlock->getDamage());
                for ($y = 1; $y < $this->groundHeight; ++$y) {
                    $chunk->setBlock($X, $y, $Z, $this->plotFillBlock->getId(), $this->plotFillBlock->getDamage());
                }
                if ($shape[$Z][$X] === 0) {
                    $chunk->setBlock($X, $this->groundHeight, $Z, $this->plotFloorBlock->getId(), $this->plotFloorBlock->getDamage());
                } elseif ($shape[$Z][$X] === 1) {
                    $chunk->setBlock($X, $this->groundHeight, $Z, $this->roadBlock->getId(), $this->roadBlock->getDamage());
                } else {
                    $chunk->setBlock($X, $this->groundHeight, $Z, $this->roadBlock->getId(), $this->roadBlock->getDamage());
                    $chunk->setBlock($X, $this->groundHeight + 1, $Z, $this->wallBlock->getId(), $this->wallBlock->getDamage());
                }
            }
        }
        $chunk->setX($chunkX);
        $chunk->setZ($chunkZ);
        $this->level->setChunk($chunkX, $chunkZ, $chunk);
    }

    public function getShape($x, $z) {
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
        $shape = array();

        for ($z = 0; $z < 16; $z++, $Z++) {
            if ($Z === $totalSize) {
                $Z = 0;
            }
            if ($Z < $this->plotSize) {
                $typeZ = 0; // plot
            } elseif ($Z === $this->plotSize or $Z === ($totalSize-1)) {
                $typeZ = 2; // wall
            } else {
                $typeZ = 1; // road
            }

            for ($x = 0, $X = $startX; $x < 16; $x++, $X++) {
                if ($X === $totalSize)
                    $X = 0;
                if ($X < $this->plotSize) {
                    $typeX = 0; // plot
                } elseif ($X === $this->plotSize or $X === ($totalSize-1)) {
                    $typeX = 2; // wall
                } else {
                    $typeX = 1; // road
                }
                if ($typeX === $typeZ) {
                    $type = $typeX;
                } elseif ($typeX === 0) {
                    $type = $typeZ;
                } elseif ($typeZ === 0) {
                    $type = $typeX;
                } else {
                    $type = 1;
                }
                $shape[$z][$x] = $type;
            }
        }
        return $shape;
    }

    public function populateChunk($chunkX, $chunkZ) {}

    public function getSpawn() {
        return new Vector3(0, $this->groundHeight, 0);
    }
}