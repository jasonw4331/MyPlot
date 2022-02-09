<?php
declare(strict_types=1);
namespace jasonwynn10\MyPlot\utils;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\world\Position;

class AsyncVariants{
	public static function array_reduce(array $array, callable $callback, mixed $initial = null) : \Generator {
		$result = $initial;
		foreach($array as $value){
			$result = yield $callback($result, $value);
		}
		return $result;
	}

	public static function getPlotPosition(Plot $plot, bool $mergeOrigin = true) : \Generator {
		$plugin = MyPlot::getInstance();
		$plotLevel = $plugin->getLevelSettings($plot->levelName);
		$origin = yield $plugin->getProvider()->getMergeOrigin($plot);
		$plotSize = $plotLevel->plotSize;
		$roadWidth = $plotLevel->roadWidth;
		$totalSize = $plotSize + $roadWidth;
		if($mergeOrigin){
			$x = $totalSize * $origin->X;
			$z = $totalSize * $origin->Z;
		}else{
			$x = $totalSize * $plot->X;
			$z = $totalSize * $plot->Z;
		}
		$level = $plugin->getServer()->getWorldManager()->getWorldByName($plot->levelName);
		return new Position($x, $plotLevel->groundHeight, $z, $level);
	}
}