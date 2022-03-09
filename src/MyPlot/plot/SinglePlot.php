<?php
declare(strict_types=1);
namespace MyPlot\plot;

use MyPlot\MyPlot;

class SinglePlot extends BasePlot {
	public string $biome = "PLAINS";
	public bool $pvp = true;
	public int $price = 0;

	public function __construct(public string $levelName, public int $X, public int $Z, public string $name = "", public string $owner = "", public array $helpers = [], public array $denied = [], string $biome = "PLAINS", ?bool $pvp = null, float $price = -1) {
		parent::__construct($levelName, $X, $Z);
		$this->biome = strtoupper($biome);
		$settings = MyPlot::getInstance()->getLevelSettings($levelName);
		if(!isset($pvp)) {
			$this->pvp = !$settings->restrictPVP;
		}else{
			$this->pvp = $pvp;
		}
		if(MyPlot::getInstance()->getEconomyProvider() !== null)
			$this->price = $price < 0 ? $settings->claimPrice : $price;
		else
			$this->price = 0;
	}

	/**
	 * @api
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	public function isHelper(string $username) : bool {
		return in_array($username, $this->helpers, true);
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
		$key = array_search($username, $this->helpers, true);
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
		return in_array($username, $this->denied, true);
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
		$key = array_search($username, $this->denied, true);
		if($key === false) {
			return false;
		}
		unset($this->denied[$key]);
		return true;
	}
}