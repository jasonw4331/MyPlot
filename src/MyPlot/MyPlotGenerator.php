<?php
namespace MyPlot;

use pocketmine\block\Block;
use pocketmine\level\generator\Generator;
use pocketmine\level\ChunkManager;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Level;

class MyPlotGenerator extends Generator
{
    /** @var Level */
    private $level;

    /** @var string[] */
    private $settings;

    /** @var Block */
    public $roadBlock, $wallBlock, $plotFloorBlock, $plotFillBlock, $bottomBlock;

    /** @var int */
    public $roadWidth, $plotSize, $groundHeight;

    const PLOT = 0;
    const ROAD = 1;
    const WALL = 2;

    public function __construct(array $settings = []) {
        if (isset($settings["preset"])) {
            $settings = json_decode($settings["preset"], true);
            if ($settings === false) {
                $settings = [];
            }
        } else {
            $settings = [];
        }
        $this->roadBlock = $this->parseBlock($settings, "RoadBlock", new Block(5));
        $this->wallBlock = $this->parseBlock($settings, "WallBlock", new Block(44));
        $this->plotFloorBlock = $this->parseBlock($settings, "PlotFloorBlock", new Block(2));
        $this->plotFillBlock = $this->parseBlock($settings, "PlotFillBlock", new Block(3));
        $this->bottomBlock = $this->parseBlock($settings, "BottomBlock", new Block(7));
        $this->roadWidth = $this->parseNumber($settings, "RoadWidth", 7);
        $this->plotSize = $this->parseNumber($settings, "PlotSize", 22);
        $this->groundHeight = $this->parseNumber($settings, "GroundHeight", 64);

        $this->settings = [];
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

    private function parseBlock(&$array, $key, $default) {
        if (isset($array[$key])) {
            $id = $array[$key];
            if (is_numeric($id)) {
                $block = new Block($id);
            } else {
                $split = explode(":", $id);
                if (count($split) === 2 and is_numeric($split[0]) and is_numeric($split[1])) {
                    $block = new Block($split[0], $split[1]);
                } else {
                    $block = $default;
                }
            }
        } else {
            $block = $default;
        }
        return $block;
    }

    private function parseNumber(&$array, $key, $default) {
        if (isset($array[$key]) and is_numeric($array[$key])) {
            return $array[$key];
        } else {
            return $default;
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
        $shape = $this->getShape($chunkX << 4, $chunkZ << 4);
        $chunk = $this->level->getChunk($chunkX, $chunkZ);
        $chunk->setGenerated();
        $c = Biome::getBiome(1)->getColor();
        $R = $c >> 16;
        $G = ($c >> 8) & 0xff;
        $B = $c & 0xff;

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
                $chunk->setBiomeColor($X, $Z, $R, $G, $B);

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

    public function populateChunk($chunkX, $chunkZ) {}

    public function getSpawn() {
        return new Vector3(0, $this->groundHeight, 0);
    }
}
