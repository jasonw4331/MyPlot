<?php
namespace MyPlot;

class Plot
{
    public $levelName, $X, $Z, $name, $owner, $helpers, $id;

    public function __construct($levelName, $X, $Z, $name = "", $owner = "", $helpers = [], $id = -1) {
        $this->levelName = $levelName;
        $this->X = $X;
        $this->Z = $Z;
        $this->name = $name;
        $this->owner = $owner;
        $this->helpers = $helpers;
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
}