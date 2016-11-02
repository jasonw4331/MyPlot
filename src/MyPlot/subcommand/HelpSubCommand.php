<?php
namespace MyPlot\subcommand;
use MyPlot\Commands;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\utils\TextFormat;
class HelpSubCommand extends SubCommand
{
	/** @var  Commands $cmd */
	private $cmd;

	public function __construct(MyPlot $plugin, $name, $commands) {
		parent::__construct($plugin, $name);
		$this->cmd = $commands;
	}

	public function canUse(CommandSender $sender) {
		return $sender->hasPermission("myplot.command.help");
	}

	public function execute(CommandSender $sender, array $args) {
		if (count($args) === 0) {
			$pageNumber = 1;
		} elseif (is_numeric($args[0])) {
			$pageNumber = (int) array_shift($args);
			if ($pageNumber <= 0) {
				$pageNumber = 1;
			}
		} else {
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			$pageHeight = PHP_INT_MAX;
		} else {
			$pageHeight = 5;
		}
		$commands = [];
		foreach ($this->cmd->getCommands() as $command) {
			if ($command->canUse($sender)) {
				$commands[$command->getName()] = $command;
			}
		}
		ksort($commands, SORT_NATURAL | SORT_FLAG_CASE);
		$commands = array_chunk($commands, $pageHeight);
		/** @var SubCommand[][] $commands */
		$pageNumber = (int) min(count($commands), $pageNumber);
		$sender->sendMessage($this->translateString("help.header", [$pageNumber, count($commands)]));
		if($sender instanceof Player) {
			foreach ($commands[$pageNumber - 1] as $command) {
				$sender->sendMessage(TextFormat::DARK_GREEN . $command->getName() . ": " . TextFormat::WHITE . $command->getDescription());
			}
		}else{
			foreach ($this->cmd->getCommands() as $subCommand) {
				$sender->sendMessage(TextFormat::DARK_GREEN . $subCommand->getName() . ": " . TextFormat::WHITE . $subCommand->getDescription());
			}
		}
		return true;
	}
}