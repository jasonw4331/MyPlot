<?php
declare(strict_types=1);
namespace MyPlot\block;

use MyPlot\MyPlot;

class Water extends \pocketmine\block\Water {
	public function onScheduledUpdate() : void {
		$plugin = MyPlot::getInstance();
		$levelName = $this->getLevel()->getFolderName();
		if($plugin->isLevelLoaded($levelName)) {
			if($plugin->getLevelSettings($levelName)->updatePlotLiquids and !is_null($plugin->getPlotByPosition($this))) {
				parent::onScheduledUpdate();
			}
		}else{
			parent::onScheduledUpdate();
		}
	}

	public function onNearbyBlockChange() : void {
		$plugin = MyPlot::getInstance();
		$levelName = $this->getLevel()->getFolderName();
		if($plugin->isLevelLoaded($levelName)) {
			if($plugin->getLevelSettings($levelName)->updatePlotLiquids and !is_null($plugin->getPlotByPosition($this))) {
				parent::onNearbyBlockChange();
			}
		}else{
			parent::onNearbyBlockChange();
		}
	}
}