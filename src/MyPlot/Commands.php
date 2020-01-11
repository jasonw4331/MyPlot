<?php
declare(strict_types=1);
namespace MyPlot;

use jasonwynn10\EasyCommandAutofill\Main;
use MyPlot\subcommand\AddHelperSubCommand;
use MyPlot\subcommand\AutoSubCommand;
use MyPlot\subcommand\BiomeSubCommand;
use MyPlot\subcommand\ClaimSubCommand;
use MyPlot\subcommand\ClearSubCommand;
use MyPlot\subcommand\CloneSubCommand;
use MyPlot\subcommand\DenyPlayerSubCommand;
use MyPlot\subcommand\DisposeSubCommand;
use MyPlot\subcommand\GenerateSubCommand;
use MyPlot\subcommand\GiveSubCommand;
use MyPlot\subcommand\HelpSubCommand;
use MyPlot\subcommand\HomesSubCommand;
use MyPlot\subcommand\HomeSubCommand;
use MyPlot\subcommand\InfoSubCommand;
use MyPlot\subcommand\ListSubCommand;
use MyPlot\subcommand\MiddleSubCommand;
use MyPlot\subcommand\NameSubCommand;
use MyPlot\subcommand\PvpSubCommand;
use MyPlot\subcommand\RemoveHelperSubCommand;
use MyPlot\subcommand\ResetSubCommand;
use MyPlot\subcommand\SetOwnerSubCommand;
use MyPlot\subcommand\SubCommand;
use MyPlot\subcommand\UnDenySubCommand;
use MyPlot\subcommand\WarpSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\CommandData;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\utils\TextFormat;

class Commands extends PluginCommand
{
	/** @var SubCommand[] $subCommands */
	private $subCommands = [];
	/** @var SubCommand[] $aliasSubCommands */
	private $aliasSubCommands = [];

	/**
	 * Commands constructor.
	 *
	 * @param MyPlot $plugin
	 */
	public function __construct(MyPlot $plugin) {
		parent::__construct($plugin->getLanguage()->get("command.name"), $plugin);
		$this->setPermission("myplot.command");
		$this->setAliases([$plugin->getLanguage()->get("command.alias")]);
		$this->setDescription($plugin->getLanguage()->get("command.desc"));
		$this->setUsage($plugin->getLanguage()->get("command.usage"));
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
		$this->loadSubCommand(new PvpSubCommand($plugin, "pvp"));
		$styler = $this->getPlugin()->getServer()->getPluginManager()->getPlugin("WorldStyler");
		if($styler !== null) {
			$this->loadSubCommand(new CloneSubCommand($plugin, "clone"));
		}
		$plugin->getLogger()->debug("Commands Registered to MyPlot");

		$autofill = $plugin->getServer()->getPluginManager()->getPlugin("EasyCommandAutofill");
		if($autofill instanceof Main) {
			$data = new CommandData();
			$data->commandName = $this->getName();
			$data->commandDescription = $this->getDescription();
			$enumCount = 0;
			$tree = 0;
			ksort($this->subCommands, SORT_NATURAL | SORT_FLAG_CASE);
			foreach($this->subCommands as $subCommandName => $subCommand) {
				$parameter = new CommandParameter();
				$parameter->paramName = "MyPlotSubCommand";
				$parameter->paramType = AvailableCommandsPacket::ARG_FLAG_ENUM | AvailableCommandsPacket::ARG_FLAG_VALID | $enumCount++;
				$enum = new CommandEnum();
				$enum->enumName = $subCommandName;
				$enum->enumValues = [$subCommandName];
				$parameter->enum = $enum;
				$parameter->flags = 1;
				$parameter->isOptional = false;
				$data->overloads[$tree][0] = $parameter;

				$usage = $subCommand->getUsage();
				$commandString = explode(" ", $usage)[0];
				preg_match_all('/(\s?[<\[]?\s*)([a-zA-Z0-9|]+)(?:\s*:?\s*)(string|int|x y z|float|mixed|target|message|text|json|command|boolean|bool)?(?:\s*[>\]]?\s?)/iu', $usage, $matches, PREG_PATTERN_ORDER, strlen($commandString));
				$argumentCount = count($matches[0])-1;
				for($argNumber = 1; $argNumber <= $argumentCount; ++$argNumber) {
					$optional = empty($matches[1][$argNumber]) ? false : ($matches[1][$argNumber] === '[');
					$paramName = strtolower($matches[2][$argNumber]);
					if(stripos($paramName, "|") === false) {
						switch(strtolower($matches[3][$argNumber])) {
							default:
							case "string":
								$paramType = AvailableCommandsPacket::ARG_FLAG_VALID | AvailableCommandsPacket::ARG_TYPE_STRING;
							break;
							case "int":
								$paramType = AvailableCommandsPacket::ARG_FLAG_VALID | AvailableCommandsPacket::ARG_TYPE_INT;
							break;
							case "x y z":
								$paramType = AvailableCommandsPacket::ARG_FLAG_VALID | AvailableCommandsPacket::ARG_TYPE_POSITION;
							break;
							case "float":
								$paramType = AvailableCommandsPacket::ARG_FLAG_VALID | AvailableCommandsPacket::ARG_TYPE_FLOAT;
							break;
							case "target":
								$paramType = AvailableCommandsPacket::ARG_FLAG_VALID | AvailableCommandsPacket::ARG_TYPE_TARGET;
							break;
							case "message":
								$paramType = AvailableCommandsPacket::ARG_FLAG_VALID | AvailableCommandsPacket::ARG_TYPE_MESSAGE;
							break;
							case "json":
								$paramType = AvailableCommandsPacket::ARG_FLAG_VALID | AvailableCommandsPacket::ARG_TYPE_JSON;
							break;
							case "command":
								$paramType = AvailableCommandsPacket::ARG_FLAG_VALID | AvailableCommandsPacket::ARG_TYPE_COMMAND;
							break;
							case "boolean":
							case "mixed":
								$paramType = AvailableCommandsPacket::ARG_FLAG_VALID | AvailableCommandsPacket::ARG_TYPE_VALUE;
							break;
							case "text":
								$paramType = AvailableCommandsPacket::ARG_FLAG_VALID | AvailableCommandsPacket::ARG_TYPE_RAWTEXT;
							break;
						}
						$parameter = new CommandParameter();
						$parameter->paramName = $paramName;
						$parameter->paramType = $paramType;
						$parameter->isOptional = $optional;
						$data->overloads[$tree][$argNumber] = $parameter;
					}else{
						$enumValues = explode("|", $paramName);
						$parameter = new CommandParameter();
						$parameter->paramName = $paramName;
						$parameter->paramType = AvailableCommandsPacket::ARG_FLAG_ENUM | AvailableCommandsPacket::ARG_FLAG_VALID | $enumCount++;
						$enum = new CommandEnum();
						$enum->enumName = $data->commandName." Enum#".$enumCount; // TODO: change to readable name
						$enum->enumValues = $enumValues;
						$parameter->enum = $enum;
						$parameter->flags = 1;
						$parameter->isOptional = $optional;
						$data->overloads[$tree][$argNumber] = $parameter;
					}
				}
				$tree++;
			}
			$data->aliases = new CommandEnum();
			$data->aliases->enumName = ucfirst($this->getName()) . "Aliases";
			$data->aliases->enumValues = array_merge([$this->getName()], $this->getAliases());
			$autofill->addManualOverride($this->getName(), $data);
			$plugin->getLogger()->debug("Command Autofill Enabled");
		}
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
	public function loadSubCommand(SubCommand $command) : void {
		$this->subCommands[$command->getName()] = $command;
		if($command->getAlias() != "") {
			$this->aliasSubCommands[$command->getAlias()] = $command;
		}
	}

	/**
	 * @param string $name
	 */
	public function unloadSubCommand(string $name) : void {
		$subcommand = $this->subCommands[$name] ?? $this->aliasSubCommands[$name] ?? null;
		if($subcommand !== null) {
			unset($this->subCommands[$subcommand->getName()]);
			unset($this->aliasSubCommands[$subcommand->getAlias()]);
		}
	}

	/**
	 * @param CommandSender $sender
	 * @param string $alias
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $alias, array $args) : bool {
		/** @var MyPlot $plugin */
		$plugin = $this->getPlugin();
		if($plugin->isDisabled()) {
			$sender->sendMessage($plugin->getLanguage()->get("plugin.disabled"));
			return true;
		}
		if(!isset($args[0])) {
			$args[0] = "help";
		}
		$subCommand = strtolower(array_shift($args));
		if(isset($this->subCommands[$subCommand])) {
			$command = $this->subCommands[$subCommand];
		}elseif(isset($this->aliasSubCommands[$subCommand])) {
			$command = $this->aliasSubCommands[$subCommand];
		}else{
			$sender->sendMessage(TextFormat::RED . $plugin->getLanguage()->get("command.unknown"));
			return true;
		}
		if($command->canUse($sender)) {
			if(!$command->execute($sender, $args)) {
				$usage = $plugin->getLanguage()->translateString("subcommand.usage", [$command->getUsage()]);
				$sender->sendMessage($usage);
			}
		}else{
			$sender->sendMessage(TextFormat::RED . $plugin->getLanguage()->get("command.unknown"));
		}
		return true;
	}
}