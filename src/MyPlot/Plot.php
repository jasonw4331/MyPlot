<?php
declare(strict_types=1);
namespace MyPlot;

use pocketmine\math\Vector3;

class Plot
{
	/** @var string $levelName */
	public $levelName = "";
	/** @var int $X */
	public $X = -0;
	/** @var int $Z */
	public $Z = -0;
	/** @var string $name */
	public $name = "";
	/** @var string $owner */
	public $owner = "";
	/** @var array $helpers */
	public $helpers = [];
	/** @var array $denied */
	public $denied = [];
	/** @var string $biome */
	public $biome = "PLAINS";
	/** @var bool $pvp */
	public $pvp = true;
	/** @var int $id */
	public $id = -1;

	/**
	 * Plot constructor.
	 *
	 * @param string $levelName
	 * @param int $X
	 * @param int $Z
	 * @param string $name
	 * @param string $owner
	 * @param array $helpers
	 * @param array $denied
	 * @param string $biome
	 * @param bool|null $pvp
	 * @param int $id
	 */
	public function __construct(string $levelName, int $X, int $Z, string $name = "", string $owner = "", array $helpers = [], array $denied = [], string $biome = "PLAINS", ?bool $pvp = null, int $id = -1) {
		$this->levelName = $levelName;
		$this->X = $X;
		$this->Z = $Z;
		$this->name = $name;
		$this->owner = $owner;
		$this->helpers = $helpers;
		$this->denied = $denied;
		$this->biome = strtoupper($biome);
		$settings = MyPlot::getInstance()->getLevelSettings($levelName);
		if(!isset($pvp) and $settings !== null) {
			$this->pvp = !$settings->restrictPVP;
		}else{
			$this->pvp = $pvp;
		}
		$this->id = $id;
	}

	/**
	 * @api
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	public function isHelper(string $username) : bool {
		return in_array($username, $this->helpers);
	}

	/**
	 * @api
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	public function addHelper(string $username) : bool {
		if(!$this->isHelper($username)) {
			$this->unDenyPlayer($username);
			$this->helpers[] = $username;
			return true;
		}
		return false;
	}

	/**
	 * @api
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	public function removeHelper(string $username) : bool {
		if(!$this->isHelper($username)) {
			return false;
		}
		$key = array_search($username, $this->helpers);
		if($key === false) {
			return false;
		}
		unset($this->helpers[$key]);
		return true;
	}

	/**
	 * @api
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	public function isDenied(string $username) : bool {
		return in_array($username, $this->denied);
	}

	/**
	 * @api
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	public function denyPlayer(string $username) : bool {
		if(!$this->isDenied($username)) {
			$this->removeHelper($username);
			$this->denied[] = $username;
			return true;
		}
		return false;
	}

	/**
	 * @api
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	public function unDenyPlayer(string $username) : bool {
		if(!$this->isDenied($username)) {
			return false;
		}
		$key = array_search($username, $this->denied);
		if($key === false) {
			return false;
		}
		unset($this->denied[$key]);
		return true;
	}

	/**
	 * @api
	 *
	 * @param Plot $plot
	 *
	 * @return bool
	 */
	public function isSame(Plot $plot) : bool {
		return $this->X === $plot->X and $this->Z === $plot->Z and $this->levelName === $plot->levelName;
	}

	/**
	 * @param int $side
	 * @param int $step
	 *
	 * @return Plot
	 */
	public function getSide(int $side, int $step = 1) : Plot {
		$levelSettings = MyPlot::getInstance()->getLevelSettings($this->levelName);
		$pos = MyPlot::getInstance()->getPlotPosition($this);
		$sidePos = $pos->getSide($side, $step * ($levelSettings->plotSize + $levelSettings->roadWidth));
		$sidePlot = MyPlot::getInstance()->getPlotByPosition($sidePos);
		if($sidePlot === null) {
			switch($side) {
				case Vector3::SIDE_NORTH:
					$sidePlot = new self($this->levelName, $this->X, $this->Z - $step);
				break;
				case Vector3::SIDE_SOUTH:
					$sidePlot = new self($this->levelName, $this->X, $this->Z + $step);
				break;
				case Vector3::SIDE_WEST:
					$sidePlot = new self($this->levelName, $this->X - $step, $this->Z);
				break;
				case Vector3::SIDE_EAST:
					$sidePlot = new self($this->levelName, $this->X + $step, $this->Z);
				break;
				default:
					return clone $this;
			}
		}
		return $sidePlot;
	}

	/**
	 * @return string
	 */
	public function __toString() : string {
		return "(" . $this->X . ";" . $this->Z . ")";
	}
}