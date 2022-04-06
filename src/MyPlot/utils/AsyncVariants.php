<?php
declare(strict_types=1);
namespace jasonwynn10\MyPlot\utils;

class AsyncVariants{
	public static function array_map(?callable $callback, array $array, array ...$arrays) : \Generator{
		$result = [];
		foreach([$array, ...$arrays] as $key => $value){
			$result[$key] = $callback === null ? $value : yield from $callback($value, $key);
		}
		return $result;
	}
}