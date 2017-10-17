<?php
namespace MyPlot;

use MyPlot\subcommand\MiddleSubCommand;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

use MyPlot\subcommand\SubCommand;
use MyPlot\subcommand\AddHelperSubCommand;
use MyPlot\subcommand\ClaimSubCommand;
use MyPlot\subcommand\ClearSubCommand;
use MyPlot\subcommand\DisposeSubCommand;
use MyPlot\subcommand\GenerateSubCommand;
use MyPlot\subcommand\HelpSubCommand;
use MyPlot\subcommand\HomeSubCommand;
use MyPlot\subcommand\InfoSubCommand;
use MyPlot\subcommand\ListSubCommand;
use MyPlot\subcommand\HomesSubCommand;
use MyPlot\subcommand\ResetSubCommand;
use MyPlot\subcommand\RemoveHelperSubCommand;
use MyPlot\subcommand\AutoSubCommand;
use MyPlot\subcommand\BiomeSubCommand;
use MyPlot\subcommand\NameSubCommand;
use MyPlot\subcommand\GiveSubCommand;
use MyPlot\subcommand\WarpSubCommand;
use MyPlot\subcommand\DenyPlayerSubCommand;
use MyPlot\subcommand\UnDenySubCommand;
use MyPlot\subcommand\SetOwnerSubCommand;

class Commands extends PluginCommand
{
	/** @var SubCommand[] */
	private $subCommands = [];

	/** @var SubCommand[]  */
	private $aliasSubCommands = [];

	/** @var MyPlot  */
	private $plugin;

	public function __construct(MyPlot $plugin) {
		  $this->plugin = $plugin;
		parent::__construct($plugin->getLanguage()->get("command.name"), $plugin);
		$this->setPermission("myplot.command");
		$this->setAliases([$plugin->getLanguage()->get("command.alias")]);
		$this->setDescription($plugin->getLanguage()->get("command.desc"));
		$this->setUsage($this->plugin->getLanguage()->get("command.usage"));

		$this->loadSubCommand(new HelpSubCommand($plugin, "help", $this));
		$this->loadSubCommand(new ClaimSubCommand($plugin, "claim"));
		$this->loadSubCommand(new GenerateSubCommand($plugin, "generate"));
		$this->loadSubCommand(new InfoSubCommand($plugin, "info"));
		$this->loadSubCommand(new AddHelperSubCommand($plugin, "addhelper"));
		$this->loadSubCommand(new RemoveHelperSubCommand($plugin, "removehelper"));
		$this->loadSubCommand(new AutoSubCommand($plugin, "auto"));
		$this->loadSubCommand(new ClearSubCommand($plugin, "clear"));
		$this->loadSubCommand(new DisposeSubCommand($plugin, "dispose"));
		$this->loadSubCommand(new ResetSubCommand($plugin, "reset"));
		$this->loadSubCommand(new BiomeSubCommand($plugin, "biome"));
		$this->loadSubCommand(new HomeSubCommand($plugin, "home"));
		$this->loadSubCommand(new HomesSubCommand($plugin, "homes"));
		$this->loadSubCommand(new NameSubCommand($plugin, "name"));
		$this->loadSubCommand(new GiveSubCommand($plugin, "give"));
		$this->loadSubCommand(new WarpSubCommand($plugin, "warp"));
		$this->loadSubCommand(new MiddleSubCommand($plugin, "middle"));
		$this->loadSubCommand(new DenyPlayerSubCommand($plugin, "denyplayer"));
		$this->loadSubCommand(new UnDenySubCommand($plugin, "undenyplayer"));
		$this->loadSubCommand(new SetOwnerSubCommand($plugin, "setowner"));
		$this->loadSubCommand(new ListSubCommand($plugin, "list"));
		$this->plugin->getLogger()->debug("Commands Registered to MyPlot");
	}

	/**
	 * @return SubCommand[]
	 */
	public function getCommands() : array {
		return $this->subCommands;
	}

	/**
	 * @param SubCommand $command
	 */
	private function loadSubCommand(SubCommand $command) {
		$this->subCommands[$command->getName()] = $command;
		if ($command->getAlias() != "") {
			$this->aliasSubCommands[$command->getAlias()] = $command;
		}
	}

	/**
	 * @param CommandSender $sender
	 * @param string $alias
	 * @param string[] $args
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $alias, array $args) {
		if (!isset($args[0])) {
			return false;
		}

		$subCommand = strtolower(array_shift($args));
		if (isset($this->subCommands[$subCommand])) {
			$command = $this->subCommands[$subCommand];
		} elseif (isset($this->aliasSubCommands[$subCommand])) {
			$command = $this->aliasSubCommands[$subCommand];
		} else {
			$sender->sendMessage(TextFormat::RED . $this->plugin->getLanguage()->get("command.unknown"));
			return true;
		}

		if ($command->canUse($sender)) {
			if (!$command->execute($sender, $args)) {
				$usage = $this->plugin->getLanguage()->translateString("subcommand.usage", [$command->getUsage()]);
				$sender->sendMessage($usage);
			}
		} else {
			$sender->sendMessage(TextFormat::RED . $this->plugin->getLanguage()->get("command.unknown"));
		}
		return true;
	}
}