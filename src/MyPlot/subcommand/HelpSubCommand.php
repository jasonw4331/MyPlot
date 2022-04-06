<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use MyPlot\Commands;
use MyPlot\InternalAPI;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class HelpSubCommand extends SubCommand{
	public function __construct(MyPlot $plugin, InternalAPI $api, string $name, private Commands $cmds){
		parent::__construct($plugin, $api, $name);
	}

	public function canUse(CommandSender $sender) : bool{
		return $sender->hasPermission("myplot.command.help");
	}

	/**
	 * @param CommandSender $sender
	 * @param string[]      $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool{
		if(count($args) === 0){
			$pageNumber = 1;
		}elseif(is_numeric($args[0])){
			$pageNumber = (int) array_shift($args);
			if($pageNumber <= 0){
				$pageNumber = 1;
			}
		}else{
			return false;
		}

		$commands = [];
		foreach($this->cmds->getCommands() as $command){
			if($command->canUse($sender)){
				$commands[$command->getName()] = $command;
			}
		}
		ksort($commands, SORT_NATURAL | SORT_FLAG_CASE);
		$commands = array_chunk($commands, (int) ($sender->getScreenLineHeight() / 2));
		/** @var SubCommand[][] $commands */
		$pageNumber = min(count($commands), $pageNumber);

		$sender->sendMessage(TextFormat::GREEN . $this->translateString("help.header", [$pageNumber, count($commands)]));
		foreach($commands[$pageNumber - 1] as $command){
			$sender->sendMessage(TextFormat::BLUE . $command->getUsage() . TextFormat::WHITE . ":");
			$sender->sendMessage(TextFormat::AQUA . $command->getDescription());
		}
		return true;
	}
}