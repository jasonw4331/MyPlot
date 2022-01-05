<?php
declare(strict_types=1);
namespace MyPlot;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds as BlockIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\biome\Biome;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\BiomeArray;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\Generator;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class MyPlotGenerator extends Generator {

	/** @var string[] $settings */
	private array $settings;
	protected Block $roadBlock;
	protected Block $bottomBlock;
	protected Block $plotFillBlock;
	protected Block $plotFloorBlock;
	protected Block $wallBlock;
	protected int $roadWidth = 7;
	protected int $groundHeight = 64;
	protected int $plotSize = 32;

	public const PLOT = 0;
	public const ROAD = 1;
	public const WALL = 2;
	public const NAME = 'myplot';

	/**
	 * MyPlotGenerator constructor.
	 *
	 * @param int $seed
	 * @param string $preset
	 */
	public function __construct(int $seed, string $preset) {
		parent::__construct($seed, $preset);
		try{
			$options = json_decode($preset, true, 512, JSON_THROW_ON_ERROR);
		}catch(\JsonException) {
			$options = [];
		}
		$this->roadBlock = PlotLevelSettings::parseBlock($options, "RoadBlock", VanillaBlocks::OAK_PLANKS());
		$this->wallBlock = PlotLevelSettings::parseBlock($options, "WallBlock", VanillaBlocks::STONE_SLAB());
		$this->plotFloorBlock = PlotLevelSettings::parseBlock($options, "PlotFloorBlock", VanillaBlocks::GRASS());
		$this->plotFillBlock = PlotLevelSettings::parseBlock($options, "PlotFillBlock", VanillaBlocks::DIRT());
		$this->bottomBlock = PlotLevelSettings::parseBlock($options, "BottomBlock", VanillaBlocks::BEDROCK());
		$this->roadWidth = PlotLevelSettings::parseNumber($options, "RoadWidth", 7);
		$this->plotSize = PlotLevelSettings::parseNumber($options, "PlotSize", 32);
		$this->groundHeight = PlotLevelSettings::parseNumber($options, "GroundHeight", 64);
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

	public function getName() : string {
		return self::NAME;
	}

	/**
	 * @return string[]
	 * @phpstan-return array<string, mixed>
	 */
	public function getSettings() : array {
		return $this->settings;
	}

	public function init(ChunkManager $level, Random $random) : void {
		$this->level = $level;
	}

	public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
		$shape = $this->getShape($chunkX << 4, $chunkZ << 4);
		$chunk = $world->getChunk($chunkX, $chunkZ) ?? new Chunk([], BiomeArray::fill(BiomeIds::PLAINS), false);
		$bottomBlockId = $this->bottomBlock->getFullId();
		$plotFillBlockId = $this->plotFillBlock->getFullId();
		$plotFloorBlockId = $this->plotFloorBlock->getFullId();
		$roadBlockId = $this->roadBlock->getFullId();
		$wallBlockId = $this->wallBlock->getFullId();
		$groundHeight = $this->groundHeight;
		for($Z = 0; $Z < 16; ++$Z) {
			for($X = 0; $X < 16; ++$X) {
				$chunk->setBiomeId($X, $Z, BiomeIds::PLAINS);
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
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return \SplFixedArray<int>
	 */
	public function getShape(int $x, int $z) : \SplFixedArray {
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
		$shape = new \SplFixedArray(1024);
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

	public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
	}

	public function getSpawn() : Vector3 {
		return new Vector3(0, $this->groundHeight + 1, 0);
	}
}