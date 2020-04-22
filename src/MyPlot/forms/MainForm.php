<?php
declare(strict_types=1);
namespace MyPlot\forms;

use MyPlot\MyPlot;
use MyPlot\subcommand\SubCommand;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
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
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle($plugin->getLanguage()->translateString("form.header", [TextFormat::DARK_BLUE."MyPlot Forms List"]));

		foreach($subCommands as $name => $command) {
			if(!$command->canUse($player) or $command->getForm() === null)
				continue;
			$name = (new \ReflectionClass($command))->getShortName();
			$name = preg_replace('/([a-z])([A-Z])/s','$1 $2', $name);
			$length = strlen($name) - strlen("Sub Command");
			$name = substr($name, 0, $length);
			$this->addButton(TextFormat::BLUE.ucfirst($name));
			$this->link[] = $command;
		}

		$this->setCallable(function(Player $player, ?MyPlotForm $data) use($plugin) {
			if(is_null($data))
				return;
			$data->setPlot($plugin->getPlotByPosition($player));
			$player->sendForm($data);
		});
	}

	public function processData(&$data) : void {
		if(is_int($data))
			$data = $this->link[$data]->getForm();
		elseif(is_null($data))
			return;
		else
			throw new FormValidationException("Unexpected form data returned");
	}
}