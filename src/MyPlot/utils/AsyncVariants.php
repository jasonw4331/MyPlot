<?php
declare(strict_types=1);
namespace jasonwynn10\MyPlot\utils;

class AsyncVariants {
	public static function array_reduce(array $array, callable $callback, mixed $initial = null) : \Generator {
		$result = $initial;
		foreach($array as $value){
			$result = yield $callback($result, $value);
		}
		return $result;
	}
}