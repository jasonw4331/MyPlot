<?php
namespace MyPlot;

use pocketmine\block\Block;
class PlotLevelSettings
{
    /** @var string */
    public $name;
    /** @var Block */
    public $roadBlock, $wallBlock, $plotFloorBlock, $plotFillBlock, $bottomBlock;
    /** @var int */
    public $roadWidth, $plotSize, $groundHeight, $claimPrice, $clearPrice,
            $disposePrice, $resetPrice;
    public $restrictEntityMovement;

    public function __construct($name, $settings = []) {
        $this->name = $name;
        if (!empty($settings)) {
            $this->roadBlock = self::parseBlock($settings, "RoadBlock", new Block(5));
            $this->wallBlock = self::parseBlock($settings, "WallBlock", new Block(44));
            $this->plotFloorBlock = self::parseBlock($settings, "PlotFloorBlock", new Block(2));
            $this->plotFillBlock = self::parseBlock($settings, "PlotFillBlock", new Block(3));
            $this->bottomBlock = self::parseBlock($settings, "BottomBlock", new Block(7));
            $this->roadWidth = self::parseNumber($settings, "RoadWidth", 7);
            $this->plotSize = self::parseNumber($settings, "PlotSize", 22);
            $this->groundHeight = self::parseNumber($settings, "GroundHeight", 64);
            $this->claimPrice = self::parseNumber($settings, "ClaimPrice", 0);
            $this->clearPrice = self::parseNumber($settings, "ClearPrice", 0);
            $this->disposePrice = self::parseNumber($settings, "DisposePrice", 0);
            $this->resetPrice = self::parseNumber($settings, "ResetPrice", 0);
            $this->restrictEntityMovement = self::parseBool($settings, "RestrictEntityMovement", true);
        }
    }

    private static function parseBlock(&$array, $key, $default) {
        if (isset($array[$key])) {
            $id = $array[$key];
            if (is_numeric($id)) {
                $block = new Block($id);
            } else {
                $split = explode(":", $id);
                if (count($split) === 2 and is_numeric($split[0]) and is_numeric($split[1])) {
                    $block = new Block($split[0], $split[1]);
                } else {
                    $block = $default;
                }
            }
        } else {
            $block = $default;
        }
        return $block;
    }

    private static function parseNumber(&$array, $key, $default) {
        if (isset($array[$key]) and is_numeric($array[$key])) {
            return $array[$key];
        } else {
            return $default;
        }
    }

    private static function parseBool(&$array, $key, $default) {
        if (isset($array[$key]) and is_bool($array[$key])) {
            return $array[$key];
        } else {
            return $default;
        }
    }
}