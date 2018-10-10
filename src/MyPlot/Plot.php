<?php
declare(strict_types=1);
namespace MyPlot;

class Plot
{

	public $levelName = "", $X = -0, $Z = -0, $name = "", $owner = "", $helpers = [], $denied = [], $biome = "PLAINS", $pvp = true, $id = -1;

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
	 * @param bool $pvp
	 * @param int $id
	 */
	public function __construct(string $levelName, int $X, int $Z, string $name = "", string $owner = "", array $helpers = [], array $denied = [], string $biome = "PLAINS", $pvp = true, int $id = -1) {
		$this->levelName = $levelName;
		$this->X = $X;
		$this->Z = $Z;
		$this->name = $name;
		$this->owner = $owner;
		$this->helpers = $helpers;
		$this->denied = $denied;
		$this->biome = strtoupper($biome);
		$settings = MyPlot::getInstance()->getLevelSettings($levelName);
		if($settings !== null) {
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
	 * @return string
	 */
	public function __toString() : string {
		return "(" . $this->X . ";" . $this->Z . ")";
	}
}