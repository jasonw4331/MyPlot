<?php
namespace MyPlot;

class Plot
{
    public $levelName, $X, $Z, $owner, $helpers, $id;

    public function __construct($levelName, $X, $Z, $owner = "", $helpers = array(), $id = -1) {
        $this->levelName = $levelName;
        $this->X = $X;
        $this->Z = $Z;
        $this->owner = $owner;
        $this->helpers = $helpers;
        $this->id = $id;
    }

    public function isHelper($username) {
        return in_array($username, $this->helpers);
    }

    public function addHelper($username) {
        if ($this->isHelper($username)) {
            $this->helpers[] = $username;
            return true;
        }
        return false;
    }

    public function removeHelper($username) {
        $key = array_search($username, $this->helpers);
        if ($key === false) {
            return false;
        }
        unset($this->helpers[$key]);
        return true;
    }
}