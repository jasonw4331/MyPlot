<?php
declare(strict_types=1);
namespace MyPlot\plot;

use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

class MergedPlot extends SinglePlot{

	public int $xWidth;
	public int $zWidth;

	public function __construct(
		string $levelName,
		int $X,
		int $Z,
		string $name = "",
		string $owner = "",
		array $helpers = [],
		array $denied = [],
		string $biome = "PLAINS",
		?bool $pvp = null,
		float $price = -1,
		int $xWidth = 0,
		int $zWidth = 0,
	){
		parent::__construct($levelName, $X, $Z, $name, $owner, $helpers, $denied, $biome, $pvp, $price);
		if($xWidth === 0 and $zWidth === 0){
			throw new \InvalidArgumentException("Plot merge width must be greater than 0");
		}
		$this->xWidth = $xWidth;
		$this->zWidth = $zWidth;
	}

	/**
	 * TODO: description
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 *
	 * @return bool
	 */
	public function isSame(BasePlot $plot) : bool {
		if(parent::isSame($plot))
			return true;

		return (
			new AxisAlignedBB(
				min($this->X, $this->X + $this->xWidth),
				0,
				min($this->Z, $this->Z + $this->zWidth),
				max($this->X, $this->X + $this->xWidth),
				1,
				max($this->Z, $this->Z + $this->zWidth)
			)
		)->isVectorInXZ(new Vector3($plot->X, 0, $plot->Z));
	}

	public function getSide(int $side, int $step = 1) : BasePlot {
		if(Facing::axis($side) === Axis::X) {
			if(Facing::isPositive($side) and $this->xWidth > 0) {
				return parent::getSide($side, $step);
			}elseif(!Facing::isPositive($side) and $this->xWidth < 0) {
				return parent::getSide($side, $step);
			}
		}elseif(Facing::axis($side) === Axis::Z){
			if(Facing::isPositive($side)and $this->zWidth > 0) {
				return parent::getSide($side, $step);
			}elseif(!Facing::isPositive($side) and $this->xWidth < 0) {
				return parent::getSide($side, $step);
			}
		}
		return parent::getSide($side, $step);
	}
}