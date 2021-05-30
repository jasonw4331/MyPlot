<?php
declare(strict_types=1);
namespace MyPlot\forms;

use dktapps\pmforms\MenuOption;
use MyPlot\MyPlot;
use MyPlot\subcommand\SubCommand;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class MainForm extends SimpleMyPlotForm {

	/** @var SubCommand[] $link */
	private $link = [];

	/**
	 * MainForm constructor.
	 *
	 * @param Player $player
	 * @param SubCommand[] $subCommands
	 *
	 * @throws \ReflectionException
	 */
	public function __construct(Player $player, array $subCommands) {
		$plugin = MyPlot::getInstance();

		$this->plot = $plugin->getPlotByPosition($player->getPosition());

		$elements = [];
		foreach($subCommands as $name => $command) {
			if(!$command->canUse($player) or $command->getForm($player) === null)
				continue;
			$name = (new \ReflectionClass($command))->getShortName();
			$name = preg_replace('/([a-z])([A-Z])/s','$1 $2', $name);
			if($name === null)
				continue;
			$length = strlen($name) - strlen("Sub Command");
			$name = substr($name, 0, $length);
			$elements[] = new MenuOption(TextFormat::DARK_RED.ucfirst($name));
			$this->link[] = $command;
		}
		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", ["Main"]),
			"",
			$elements,
			function(Player $player, int $selectedOption) : void {
				$form = $this->link[$selectedOption]->getForm($player);
				if($form === null)
					return;
				$form->setPlot($this->plot);
				$player->sendForm($form);
			},
			function(Player $player) : void {}
		);
	}
}