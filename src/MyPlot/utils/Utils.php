<?php
declare(strict_types=1);

namespace jasonwynn10\MyPlot\utils;

use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;

class Utils{
	public static function rotateAxisAlignedBB(AxisAlignedBB $alignedBB, int $axis) : AxisAlignedBB{
		$width = $alignedBB->maxX - $alignedBB->minX;
		$height = $alignedBB->maxY - $alignedBB->minY;
		$length = $alignedBB->maxZ - $alignedBB->minZ;
		if($width <= 0 or $length <= 0 or $height <= 0)
			throw new \InvalidArgumentException("AxisAlignedBB cannot have a width, length, or height of 0");

		return match ($axis) {
			Axis::X => new AxisAlignedBB(0, 0, 0, $width, $length, $height),
			Axis::Y => new AxisAlignedBB(0, 0, 0, $length, $height, $width),
			Axis::Z => new AxisAlignedBB(0, 0, 0, $height, $width, $length),
			default => throw new \InvalidArgumentException("Invalid axis $axis")
		};
	}
}