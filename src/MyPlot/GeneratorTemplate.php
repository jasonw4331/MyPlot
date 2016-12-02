<?php
namespace MyPlot;

use pocketmine\block\Block;
use pocketmine\level\generator\Generator;
use pocketmine\level\ChunkManager;
use pocketmine\level\Level;
use pocketmine\utils\Random;

abstract class GeneratorTemplate extends Generator
{

	public static $name = "GeneratorTemplate";

	/** @var  Level */
	protected $level;

	/** @var string[] */
	protected $settings;

	/** @var Block */
	protected $roadBlock, $wallBlock, $plotFloorBlock, $plotFillBlock, $bottomBlock;

	/** @var int */
	protected $roadWidth, $plotSize, $groundHeight;

	const PLOT = 0;
	const ROAD = 1;
	const WALL = 2;

	public function __construct(array $settings = []) {
		if (isset($settings["preset"])) {
			$settings = json_decode($settings["preset"], true);
			if ($settings === false) {
				$this->settings = [];
			}else{
				$this->settings = $settings;
			}
		}else{
			$this->settings = [];
		}
	}

	protected static function parseBlock(&$array, $key, $default) {
		if (isset($array[$key])) {
			$id = $array[$key];
			if (is_numeric($id)) {
				$block = new Block($id);
			}else{
				$split = explode(":", $id);
				if (count($split) === 2 and is_numeric($split[0]) and is_numeric($split[1])) {
					$block = new Block($split[0], $split[1]);
				}else{
					$block = $default;
				}
			}
		}else{
			$block = $default;
		}
		return $block;
	}

	protected static function parseNumber(&$array, $key, $default) {
		if (isset($array[$key]) and is_numeric($array[$key])) {
			return $array[$key];
		}else{
			return $default;
		}
	}

	public function getName() {}

	public function getSettings() {
		return $this->settings;
	}

	public function init(ChunkManager $level, Random $random) {}

	public function generateChunk($chunkX, $chunkZ) {}

	public function getShape($x, $z) {}

	public function populateChunk($chunkX, $chunkZ) {}

	public function getSpawn() {}
}