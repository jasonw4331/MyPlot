<?php
namespace MyPlot;

class Plot
{
	public $levelName, $X, $Z, $name = "", $owner = "", $helpers = [], $denied = [], $biome = "PLAINS", $id = -1;

	/**
	 * @param string $levelName
	 * @param int $X
	 * @param int $Z
	 * @param string $name
	 * @param string $owner
	 * @param array $helpers
	 * @param array $denied
	 * @param string $biome
	 * @param int $id
	 */
	public function __construct(string $levelName, int $X, int $Z, string $name = "", string $owner = "", array $helpers = [], array $denied = [], string $biome = "PLAINS", int $id = -1) {
		$this->levelName = $levelName;
		$this->X = $X;
		$this->Z = $Z;
		$this->name = $name;
		$this->owner = $owner;
		$this->helpers = $helpers;
		$this->denied = $denied;
		$this->biome = strtoupper($biome);
		$this->id = $id;
	}

	/**
	 * @api
	 * @param string $username
	 * @return bool
	 */
	public function isHelper($username) {
		return in_array($username, $this->helpers);
	}

	/**
	 * @api
	 * @param string $username
	 * @return bool
	 */
	public function addHelper($username) {
		if (!$this->isHelper($username)) {
			$this->unDenyPlayer($username);
			$this->helpers[] = $username;
			return true;
		}
		return false;
	}

	/**
	 * @api
	 * @param string $username
	 * @return bool
	 */
	public function removeHelper($username) {
		if(!$this->isHelper($username)) {
			return false;
		}
		$key = array_search($username, $this->helpers);
		if ($key === false) {
			return false;
		}
		unset($this->helpers[$key]);
		return true;
	}

	/**
	 * @api
	 * @param string $username
	 * @return bool
	 */
	public function isDenied($username) {
		return in_array($username, $this->denied);
	}

	/**
	 * @api
	 * @param string $username
	 * @return bool
	 */
	public function denyPlayer($username) {
		if (!$this->isDenied($username)) {
			$this->removeHelper($username);
			$this->denied[] = $username;
			return true;
		}
		return false;
	}

	/**
	 * @api
	 * @param string $username
	 * @return bool
	 */
	public function unDenyPlayer($username) {
		if(!$this->isDenied($username)) {
			return false;
		}
		$key = array_search($username, $this->denied);
		if ($key === false) {
			return false;
		}
		unset($this->denied[$key]);
		return true;
	}

	public function __toString() {
		return "(" . $this->X . ";" . $this->Z . ")";
	}
}
