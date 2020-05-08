<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CloneForm extends ComplexMyPlotForm {

	/** @var Player $player */
	private $player;

	public function __construct(Player $player) {
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle(TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", ["Clone Form"]));

		$this->addLabel("Origin Plot Location");
		$this->addInput("Plot X Coordinate", "2");
		$this->addInput("Plot Z Coordinate", "-4");
		$this->addInput("Plot World Name", "world", $player->getLevel()->getFolderName());

		$this->addLabel("Clone Plot Location");
		$this->addInput("Plot X Coordinate", "2");
		$this->addInput("Plot Z Coordinate", "-4");
		$this->addInput("Plot World Name", "world", $player->getLevel()->getFolderName());

		$this->setCallable(function(Player $player, ?array $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			/** @var Plot $originPlot */
			$originPlot = $data[0];
			/** @var Plot $clonedPlot */
			$clonedPlot = $data[1];
			if($originPlot->owner !== $player->getName() and !$player->hasPermission("myplot.admin.clone")) {
				$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("notowner"));
				return;
			}
			if($clonedPlot->owner !== $player->getName() and !$player->hasPermission("myplot.admin.clone")) {
				$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("notowner"));
				return;
			}
			$plotLevel = $plugin->getLevelSettings($originPlot->levelName);
			$economy = $plugin->getEconomyProvider();
			if($economy !== null and !$economy->reduceMoney($player, $plotLevel->clonePrice)) {
				$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("clone.nomoney"));
				return;
			}
			if($plugin->clonePlot($originPlot, $clonedPlot)) {
				$player->sendMessage($plugin->getLanguage()->translateString("clone.success", [$clonedPlot->__toString(), $originPlot->__toString()]));
			}else{
				$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("error"));
			}
		});
	}

	public function handleResponse(Player $player, $data) : void {
		$this->player = $player;
		parent::handleResponse($player, $data);
	}

	public function processData(&$data) : void {
		if(is_null($data))
			return;
		elseif(is_array($data) and is_numeric($data[1]) and is_numeric($data[2]) and is_numeric($data[5]) and is_numeric($data[6])) {
			$newData = [];
			$newData[] = MyPlot::getInstance()->getProvider()->getPlot(empty($data[3]) ? $this->player->getLevel()->getFolderName() : $data[3], (int)$data[1], (int)$data[2]);
			$newData[] = MyPlot::getInstance()->getProvider()->getPlot(empty($data[7]) ? $this->player->getLevel()->getFolderName() : $data[7], (int)$data[5], (int)$data[6]);
			$data = $newData;
		}else
			throw new FormValidationException("Unexpected form data returned");
	}
}