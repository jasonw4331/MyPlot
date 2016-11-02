<?php
namespace MyPlot;

class Plot
{
    public $levelName, $X, $Z, $name, $owner, $helpers, $biome, $id, $denied;

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
     * @param boolean $done
     */
    public function __construct($levelName, $X, $Z, $name = "", $owner = "", $helpers = [], $denied = [], $biome = "PLAINS", $id = -1, $done = false) {
        $this->levelName = $levelName;
        $this->X = $X;
        $this->Z = $Z;
        $this->name = $name;
        $this->owner = $owner;
        $this->helpers = $helpers;
        $this->denied = $denied;
        $this->biome = $biome;
        $this->id = $id;
        $this->done = $done;
    }

    /**
     * @param string $username
     * @return bool
     */
    public function isHelper($username) {
        return in_array($username, $this->helpers);
    }

    /**
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
     * @param string $username
     * @return bool
     */
    public function removeHelper($username) {
        $key = array_search($username, $this->helpers);
        if ($key === false) {
            return false;
        }
        unset($this->helpers[$key]);
        return true;
    }

    /**
     * @param string $username
     * @return bool
     */
    public function isDenied($username) {
        return in_array($username, $this->denied);
    }

    /**
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
     * @param string $username
     * @return bool
     */
    public function unDenyPlayer($username) {
        if($this->isDenied($username)) {
            return true;
        }
        $key = array_search($username, $this->denied);
        if ($key === false) {
            return false;
        }
        unset($this->denied[$key]);
        return true;
    }

    /**
     * @return bool
     */
    public function toggleDone() {
        $this->done = !$this->done;
        return $this->done;
    }

    public function __toString() {
        return "(" . $this->X . ";" . $this->Z . ")";
    }
}
