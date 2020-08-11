<?php
declare(strict_types=1);

namespace MyPlot\forms\subforms;


use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class WarpForm extends ComplexMyPlotForm {
	/** @var Player $player */
	private $player;

	public function __construct(Player $player) {
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle(TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("warp.form")]));

		$this->addInput($plugin->getLanguage()->get("warp.formxcoord"), "2");
		$this->addInput($plugin->getLanguage()->get("warp.formzcoord"), "-4");
		$this->addInput($plugin->getLanguage()->get("warp.formworld"), "world", $player->getLevel()->getFolderName());

		$this->setCallable(function(Player $player, ?array $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("warp.name")." ".$data[0].";".$data[1]." \"$data[2]\"", true);
		});
	}

	public function handleResponse(Player $player, $data) : void {
		$this->player = $player;
		parent::handleResponse($player, $data);
	}

	public function processData(&$data) : void {
		if(is_null($data))
			return;
		elseif(is_array($data) and is_numeric($data[0]) and is_numeric($data[1]))
			$data =[
				(int)$data[0],
				(int)$data[1],
				empty($data[2]) ? $this->player->getLevel()->getFolderName() : $data[2]
			];
		elseif(is_array($data) and empty($data[0]) and empty($data[1])) {
			$this->player->sendForm(new self($this->player));
			throw new FormValidationException("Invalid form data returned");
		}else
			throw new FormValidationException("Unexpected form data returned");
	}
}