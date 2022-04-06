<?php
declare(strict_types=1);
namespace MyPlot;

use jasonwynn10\EasyCommandAutofill\Main;
use MyPlot\forms\MainForm;
use MyPlot\subcommand\AddHelperSubCommand;
use MyPlot\subcommand\AutoSubCommand;
use MyPlot\subcommand\BiomeSubCommand;
use MyPlot\subcommand\BuySubCommand;
use MyPlot\subcommand\ClaimSubCommand;
use MyPlot\subcommand\ClearSubCommand;
use MyPlot\subcommand\CloneSubCommand;
use MyPlot\subcommand\DenyPlayerSubCommand;
use MyPlot\subcommand\DisposeSubCommand;
use MyPlot\subcommand\FillSubCommand;
use MyPlot\subcommand\GenerateSubCommand;
use MyPlot\subcommand\GiveSubCommand;
use MyPlot\subcommand\HelpSubCommand;
use MyPlot\subcommand\HomesSubCommand;
use MyPlot\subcommand\HomeSubCommand;
use MyPlot\subcommand\InfoSubCommand;
use MyPlot\subcommand\KickSubCommand;
use MyPlot\subcommand\ListSubCommand;
use MyPlot\subcommand\MergeSubCommand;
use MyPlot\subcommand\MiddleSubCommand;
use MyPlot\subcommand\MyPlotSubCommand;
use MyPlot\subcommand\NameSubCommand;
use MyPlot\subcommand\PvpSubCommand;
use MyPlot\subcommand\RemoveHelperSubCommand;
use MyPlot\subcommand\ResetSubCommand;
use MyPlot\subcommand\SellSubCommand;
use MyPlot\subcommand\SetOwnerSubCommand;
use MyPlot\subcommand\UnDenySubCommand;
use MyPlot\subcommand\WarpSubCommand;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use pocketmine\utils\TextFormat;

class Commands extends Command implements PluginOwned{
	use PluginOwnedTrait;

	/** @var MyPlotSubCommand[] $subCommands */
	private array $subCommands = [];
	/** @var MyPlotSubCommand[] $aliasSubCommands */
	private array $aliasSubCommands = [];

	public function __construct(MyPlot $owningPlugin, private InternalAPI $internalAPI){
		parent::__construct($owningPlugin->getLanguage()->get("command.name"), $owningPlugin->getLanguage()->get("command.desc"), $owningPlugin->getLanguage()->get("command.usage"), [$owningPlugin->getLanguage()->get("command.alias")]);
		$this->setPermission("myplot.command");
		$this->owningPlugin = $owningPlugin;
		$this->loadSubCommand(new HelpSubCommand($owningPlugin, $internalAPI, "help", $this));
		$this->loadSubCommand(new ClaimSubCommand($owningPlugin, $internalAPI, "claim"));
		$this->loadSubCommand(new GenerateSubCommand($owningPlugin, $internalAPI, "generate"));
		$this->loadSubCommand(new InfoSubCommand($owningPlugin, $internalAPI, "info"));
		$this->loadSubCommand(new AddHelperSubCommand($owningPlugin, $internalAPI, "addhelper"));
		$this->loadSubCommand(new RemoveHelperSubCommand($owningPlugin, $internalAPI, "removehelper"));
		$this->loadSubCommand(new AutoSubCommand($owningPlugin, $internalAPI, "auto"));
		$this->loadSubCommand(new ClearSubCommand($owningPlugin, $internalAPI, "clear"));
		$this->loadSubCommand(new FillSubCommand($owningPlugin, $internalAPI, "fill"));
		$this->loadSubCommand(new DisposeSubCommand($owningPlugin, $internalAPI, "dispose"));
		$this->loadSubCommand(new ResetSubCommand($owningPlugin, $internalAPI, "reset"));
		$this->loadSubCommand(new BiomeSubCommand($owningPlugin, $internalAPI, "biome"));
		$this->loadSubCommand(new HomeSubCommand($owningPlugin, $internalAPI, "home"));
		$this->loadSubCommand(new HomesSubCommand($owningPlugin, $internalAPI, "homes"));
		$this->loadSubCommand(new NameSubCommand($owningPlugin, $internalAPI, "name"));
		$this->loadSubCommand(new GiveSubCommand($owningPlugin, $internalAPI, "give"));
		$this->loadSubCommand(new WarpSubCommand($owningPlugin, $internalAPI, "warp"));
		$this->loadSubCommand(new MiddleSubCommand($owningPlugin, $internalAPI, "middle"));
		$this->loadSubCommand(new DenyPlayerSubCommand($owningPlugin, $internalAPI, "denyplayer"));
		$this->loadSubCommand(new UnDenySubCommand($owningPlugin, $internalAPI, "undenyplayer"));
		$this->loadSubCommand(new SetOwnerSubCommand($owningPlugin, $internalAPI, "setowner"));
		$this->loadSubCommand(new ListSubCommand($owningPlugin, $internalAPI, "list"));
		$this->loadSubCommand(new PvpSubCommand($owningPlugin, $internalAPI, "pvp"));
		$this->loadSubCommand(new KickSubCommand($owningPlugin, $internalAPI, "kick"));
		$this->loadSubCommand(new MergeSubCommand($owningPlugin, $internalAPI, "merge"));
		if($internalAPI->getEconomyProvider() !== null){
			$this->loadSubCommand(new SellSubCommand($owningPlugin, $internalAPI, "sell"));
			$this->loadSubCommand(new BuySubCommand($owningPlugin, $internalAPI, "buy"));
		}
		$styler = $owningPlugin->getServer()->getPluginManager()->getPlugin("WorldStyler");
		if($styler !== null){
			$this->loadSubCommand(new CloneSubCommand($owningPlugin, $internalAPI, "clone"));
		}
		$owningPlugin->getLogger()->debug("Commands Registered to MyPlot");

		$autofill = $owningPlugin->getServer()->getPluginManager()->getPlugin("EasyCommandAutofill");
		if($autofill instanceof Main and $autofill->getDescription()->getVersion() === '3.0.2'){
			$overloads = [];
			$enumCount = 0;
			$tree = 0;
			ksort($this->subCommands, SORT_NATURAL | SORT_FLAG_CASE);
			foreach($this->subCommands as $subCommandName => $subCommand){
				$overloads[$tree][0] = CommandParameter::enum("MyPlotSubCommand", new CommandEnum($subCommandName, [$subCommandName]), CommandParameter::FLAG_FORCE_COLLAPSE_ENUM, false);

				$usage = $subCommand->getUsage();
				$commandString = explode(" ", $usage)[0];
				preg_match_all('/\h*([<\[])?\h*([\w|]+)\h*:?\h*([\w\h]+)?\h*[>\]]?\h*/iu', $usage, $matches, PREG_PATTERN_ORDER, strlen($commandString)); // https://regex101.com/r/1REoJG/22
				$argumentCount = count($matches[0]) - 1;
				for($argNumber = 1; $argNumber <= $argumentCount; ++$argNumber){
					if(!isset($matches[1][$argNumber])){
						$paramName = strtolower($matches[2][$argNumber]);
						$paramName = $paramName === 'bool' ? 'Boolean' : $paramName;

						$softEnums = $autofill->getSoftEnums();
						if(isset($softEnums[$paramName])){
							$enum = $softEnums[$paramName];
						}else{
							$autofill->addSoftEnum($enum = new CommandEnum($paramName, [$paramName]), false);
						}
						$overloads[$tree][$argNumber] = CommandParameter::enum($paramName, $enum, CommandParameter::FLAG_FORCE_COLLAPSE_ENUM, false); // collapse and assume required because no optional identifier exists in usage message
						continue;
					}
					$optional = str_contains($matches[1][$argNumber], '[');
					$paramName = strtolower($matches[2][$argNumber]);
					$paramType = strtolower($matches[3][$argNumber] ?? '');
					if(in_array($paramType, array_keys(array_merge($autofill->getSoftEnums(), $autofill->getHardcodedEnums())), true)){
						$paramType = $paramType === 'bool' ? 'Boolean' : $paramType;
						$enum = $autofill->getSoftEnums()[$paramType] ?? $autofill->getHardcodedEnums()[$paramType];
						$overloads[$tree][$argNumber] = CommandParameter::enum($paramName, $enum, 0, $optional);
					}elseif(str_contains($paramName, "|")){
						++$enumCount;
						$enumValues = explode("|", $paramName);
						$autofill->addSoftEnum($enum = new CommandEnum($subCommandName . " Enum#" . $enumCount, $enumValues), false);
						$overloads[$tree][$argNumber] = CommandParameter::enum($paramName, $enum, CommandParameter::FLAG_FORCE_COLLAPSE_ENUM, $optional);
					}elseif(str_contains($paramName, "/")){
						++$enumCount;
						$enumValues = explode("/", $paramName);
						$autofill->addSoftEnum($enum = new CommandEnum($subCommandName . " Enum#" . $enumCount, $enumValues), false);
						$overloads[$tree][$argNumber] = CommandParameter::enum($paramName, $enum, CommandParameter::FLAG_FORCE_COLLAPSE_ENUM, $optional);
					}else{
						$paramType = match ($paramType) { // ordered by constant value
							'int' => AvailableCommandsPacket::ARG_TYPE_INT,
							'float' => AvailableCommandsPacket::ARG_TYPE_FLOAT,
							'mixed' => AvailableCommandsPacket::ARG_TYPE_VALUE,
							'player', 'target' => AvailableCommandsPacket::ARG_TYPE_TARGET,
							'string' => AvailableCommandsPacket::ARG_TYPE_STRING,
							'x y z' => AvailableCommandsPacket::ARG_TYPE_POSITION,
							'message' => AvailableCommandsPacket::ARG_TYPE_MESSAGE,
							default => AvailableCommandsPacket::ARG_TYPE_RAWTEXT,
							'json' => AvailableCommandsPacket::ARG_TYPE_JSON,
							'command' => AvailableCommandsPacket::ARG_TYPE_COMMAND,
						};
						$overloads[$tree][$argNumber] = CommandParameter::standard($paramName, $paramType, 0, $optional);
					}
				}
				$tree++;
			}
			$data = $autofill->generateGenericCommandData($this->getName(), $this->getAliases(), $this->getDescription(), $this->getUsage());
			$data->overloads = $overloads;
			$autofill->addManualOverride('myplot:' . $this->getName(), $data);
			$owningPlugin->getLogger()->debug("Command Autofill Enabled");
		}
	}

	/**
	 * @return MyPlotSubCommand[]
	 */
	public function getCommands() : array{
		return $this->subCommands;
	}

	public function loadSubCommand(MyPlotSubCommand $command) : void{
		$this->subCommands[$command->getName()] = $command;
		if($command->getAlias() != ""){
			$this->aliasSubCommands[$command->getAlias()] = $command;
		}
	}

	public function unloadSubCommand(string $name) : void{
		$subcommand = $this->subCommands[$name] ?? $this->aliasSubCommands[$name] ?? null;
		if($subcommand !== null){
			unset($this->subCommands[$subcommand->getName()]);
			unset($this->aliasSubCommands[$subcommand->getAlias()]);
		}
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($this->owningPlugin->isDisabled()){
			$sender->sendMessage($this->owningPlugin->getLanguage()->get("plugin.disabled"));
			return true;
		}
		if(!isset($args[0])){
			$args[0] = "help";
			if($sender instanceof Player and $this->owningPlugin->getConfig()->get("UI Forms", true) === true and class_exists('cosmicpe\\form\\PaginatedForm')){
				$sender->sendForm(new MainForm(1, $sender, $this->owningPlugin, $this->internalAPI));
				return true;
			}
		}
		$subCommand = strtolower((string) array_shift($args));
		if(isset($this->subCommands[$subCommand])){
			$command = $this->subCommands[$subCommand];
		}elseif(isset($this->aliasSubCommands[$subCommand])){
			$command = $this->aliasSubCommands[$subCommand];
		}else{
			$sender->sendMessage(TextFormat::RED . $this->owningPlugin->getLanguage()->get("command.unknown"));
			return true;
		}
		if(!$command->canUse($sender)){
			$sender->sendMessage(TextFormat::RED . $this->owningPlugin->getLanguage()->get("command.usable"));
			return true;
		}
		if(!$command->execute($sender, $args)){
			$sender->sendMessage($this->owningPlugin->getLanguage()->translateString("subcommand.usage", [$command->getUsage()]));
		}
		return true;
	}
}