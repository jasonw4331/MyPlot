<?php
declare(strict_types=1);
namespace MyPlot\forms;

use dktapps\pmforms\MenuForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\player\Player;

abstract class SimpleMyPlotForm extends MenuForm implements MyPlotForm {
	/** @var Plot|null $plot */
	protected $plot;

	public function __construct(string $title, string $text, array $options, \Closure $onSubmit, ?\Closure $onClose = null) {
		parent::__construct($title, $text, $options, $onSubmit,
			$onClose ?? function(Player $player) : void {
				$player->getServer()->dispatchCommand($player, MyPlot::getInstance()->getLanguage()->get("command.name"), true);
			}
		);
	}

	public function setPlot(?Plot $plot) : void {
		$this->plot = $plot;
	}

	public function getPlot() : ?Plot {
		return $this->plot;
	}
}