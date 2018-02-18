<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\Commands;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class HelpSubCommand extends SubCommand
{
	/** @var Commands $cmds */
	private $cmds;

	/**
	 * HelpSubCommand constructor.
	 *
	 * @param MyPlot $plugin
	 * @param string $name
	 * @param Commands $cmds
	 */
	public function __construct(MyPlot $plugin, string $name, Commands $cmds) {
		parent::__construct($plugin, $name);
		$this->cmds = $cmds;
	}

	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return $sender->hasPermission("myplot.command.help");
	}

	/**
	 * @param CommandSender $sender
	 * @param string[] $args
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(empty($args)) {
			$pageNumber = 1;
		}elseif(is_numeric($args[0])) {
			$pageNumber = (int) array_shift($args);
			if ($pageNumber <= 0) {
				$pageNumber = 1;
			}
		}else{
			return false;
		}

		$commands = [];
		foreach($this->cmds->getCommands() as $command) {
			if ($command->canUse($sender)) {
				$commands[$command->getName()] = $command;
			}
		}
		ksort($commands, SORT_NATURAL | SORT_FLAG_CASE);
		$commands = array_chunk($commands, $sender->getScreenLineHeight());
		/** @var SubCommand[][] $commands */
		$pageNumber = (int) min(count($commands), $pageNumber);

		$sender->sendMessage($this->translateString("help.header", [$pageNumber, count($commands)]));
		foreach($commands[$pageNumber - 1] as $command) {
			$sender->sendMessage(TextFormat::DARK_GREEN . $command->getName() . ": " . TextFormat::WHITE . $command->getDescription());
		}
		return true;
	}
}