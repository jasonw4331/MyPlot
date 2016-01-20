<?php
namespace MyPlot;

class Plot
{
    public $levelName, $X, $Z, $name, $owner, $helpers, $biome, $id;

    /**
     * @param string $levelName
     * @param int $X
     * @param int $Z
     * @param string $name
     * @param string $owner
     * @param array $helpers
     * @param string $biome
     * @param int $id
     */
    public function __construct($levelName, $X, $Z, $name = "", $owner = "", $helpers = [], $biome = "PLAINS", $id = -1) {
        $this->levelName = $levelName;
        $this->X = $X;
        $this->Z = $Z;
        $this->name = $name;
        $this->owner = $owner;
        $this->helpers = $helpers;
        $this->biome = $biome;
        $this->id = $id;
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

    public function __toString() {
        return "(" . $this->X . ";" . $this->Z . ")";
    }
}