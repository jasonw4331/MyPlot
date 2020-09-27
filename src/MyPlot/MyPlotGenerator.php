<?php
declare(strict_types=1);
namespace MyPlot;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\biome\Biome;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;

class MyPlotGenerator extends Generator {
	/** @var Block $roadBlock */
	protected $roadBlock;
	/** @var Block $bottomBlock */
	protected $bottomBlock;
	/** @var Block $plotFillBlock */
	protected $plotFillBlock;
	/** @var Block $plotFloorBlock */
	protected $plotFloorBlock;
	/** @var Block $wallBlock */
	protected $wallBlock;
	/** @var int $roadWidth */
	protected $roadWidth = 7;
	/** @var int $groundHeight */
	protected $groundHeight = 64;
	/** @var int $plotSize */
	protected $plotSize = 32;
	const PLOT = 0;
	const ROAD = 1;
	const WALL = 2;

	/**
	 * MyPlotGenerator constructor.
	 *
	 * @param ChunkManager $world
	 * @param int $seed
	 * @param array $settings
	 */
	public function __construct(ChunkManager $world, int $seed, array $settings = []) {
		parent::__construct($world, $seed, $settings);
		if(isset($settings["preset"])) {
			$settings = json_decode($settings["preset"], true);
			if($settings === false or is_null($settings)) {
				$settings = [];
			}
		}else{
			$settings = [];
		}
		$this->roadBlock = PlotLevelSettings::parseBlock($settings, "RoadBlock", VanillaBlocks::OAK_PLANKS());
		$this->wallBlock = PlotLevelSettings::parseBlock($settings, "WallBlock", VanillaBlocks::STONE_SLAB());
		$this->plotFloorBlock = PlotLevelSettings::parseBlock($settings, "PlotFloorBlock", VanillaBlocks::GRASS());
		$this->plotFillBlock = PlotLevelSettings::parseBlock($settings, "PlotFillBlock", VanillaBlocks::DIRT());
		$this->bottomBlock = PlotLevelSettings::parseBlock($settings, "BottomBlock", VanillaBlocks::BEDROCK());
		$this->roadWidth = PlotLevelSettings::parseNumber($settings, "RoadWidth", 7);
		$this->plotSize = PlotLevelSettings::parseNumber($settings, "PlotSize", 32);
		$this->groundHeight = PlotLevelSettings::parseNumber($settings, "GroundHeight", 64);
		$this->settings = [];
		$this->settings["preset"] = (string)json_encode([
			"RoadBlock" => $this->roadBlock->getId() . (($meta = $this->roadBlock->getMeta()) === 0 ? '' : ':' . $meta),
			"WallBlock" => $this->wallBlock->getId() . (($meta = $this->wallBlock->getMeta()) === 0 ? '' : ':' . $meta),
			"PlotFloorBlock" => $this->plotFloorBlock->getId() . (($meta = $this->plotFloorBlock->getMeta()) === 0 ? '' : ':' . $meta),
			"PlotFillBlock" => $this->plotFillBlock->getId() . (($meta = $this->plotFillBlock->getMeta()) === 0 ? '' : ':' . $meta),
			"BottomBlock" => $this->bottomBlock->getId() . (($meta = $this->bottomBlock->getMeta()) === 0 ? '' : ':' . $meta),
			"RoadWidth" => $this->roadWidth,
			"PlotSize" => $this->plotSize,
			"GroundHeight" => $this->groundHeight
		]);
	}

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 */
	public function generateChunk(int $chunkX, int $chunkZ) : void {
		$shape = $this->getShape($chunkX << 4, $chunkZ << 4);
		$chunk = $this->world->getChunk($chunkX, $chunkZ);
		$bottomBlockId = $this->bottomBlock->getFullId();
		$plotFillBlockId = $this->plotFillBlock->getFullId();
		$plotFloorBlockId = $this->plotFloorBlock->getFullId();
		$roadBlockId = $this->roadBlock->getFullId();
		$wallBlockId = $this->wallBlock->getFullId();
		$groundHeight = $this->groundHeight;
		for($Z = 0; $Z < 16; ++$Z) {
			for($X = 0; $X < 16; ++$X) {
				$chunk->setBiomeId($X, $Z, Biome::PLAINS);
				$chunk->setFullBlock($X, 0, $Z, $bottomBlockId);
				$chunk->setFullBlock($X, 0, $Z, $bottomBlockId);
				for($y = 1; $y < $groundHeight; ++$y) {
					$chunk->setFullBlock($X, $y, $Z, $plotFillBlockId);
				}
				$type = $shape[($Z << 4) | $X];
				if($type === self::PLOT) {
					$chunk->setFullBlock($X, $groundHeight, $Z, $plotFloorBlockId);
				}elseif($type === self::ROAD) {
					$chunk->setFullBlock($X, $groundHeight, $Z, $roadBlockId);
				}else{
					$chunk->setFullBlock($X, $groundHeight, $Z, $roadBlockId);
					$chunk->setFullBlock($X, $groundHeight + 1, $Z, $wallBlockId);
				}
			}
		}
		$chunk->setX($chunkX);
		$chunk->setZ($chunkZ);
		$chunk->setGenerated();
		$this->world->setChunk($chunkX, $chunkZ, $chunk);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return \SplFixedArray<int>
	 */
	public function getShape(int $x, int $z) {
		$totalSize = $this->plotSize + $this->roadWidth;
		if($x >= 0) {
			$X = $x % $totalSize;
		}else{
			$X = $totalSize - abs($x % $totalSize);
		}
		if($z >= 0) {
			$Z = $z % $totalSize;
		}else{
			$Z = $totalSize - abs($z % $totalSize);
		}
		$startX = $X;
		$shape = new \SplFixedArray(256);
		for($z = 0; $z < 16; $z++, $Z++) {
			if($Z === $totalSize) {
				$Z = 0;
			}
			if($Z < $this->plotSize) {
				$typeZ = self::PLOT;
			}elseif($Z === $this->plotSize or $Z === ($totalSize - 1)) {
				$typeZ = self::WALL;
			}else{
				$typeZ = self::ROAD;
			}
			for($x = 0, $X = $startX; $x < 16; $x++, $X++) {
				if($X === $totalSize) {
					$X = 0;
				}
				if($X < $this->plotSize) {
					$typeX = self::PLOT;
				}elseif($X === $this->plotSize or $X === ($totalSize - 1)) {
					$typeX = self::WALL;
				}else{
					$typeX = self::ROAD;
				}
				if($typeX === $typeZ) {
					$type = $typeX;
				}elseif($typeX === self::PLOT) {
					$type = $typeZ;
				}elseif($typeZ === self::PLOT) {
					$type = $typeX;
				}else{
					$type = self::ROAD;
				}
				$shape[($z << 4) | $x] = $type;
			}
		}
		return $shape;
	}

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 */
	public function populateChunk(int $chunkX, int $chunkZ) : void {
	}
}