<?php
declare(strict_types=1);
namespace MyPlot\block;

use MyPlot\MyPlot;

class Water extends \pocketmine\block\Water {
	public function onUpdate(int $type) {
		$plugin = MyPlot::getInstance();
		$levelName = $this->getLevel()->getFolderName();
		if($plugin->isLevelLoaded($levelName)) {
			if($plugin->getLevelSettings($levelName)->updatePlotLiquids and !is_null($plugin->getPlotByPosition($this))) {
				return parent::onUpdate($type);
			}else{
				return false;
			}
		}else{
			return parent::onUpdate($type);
		}
	}
}