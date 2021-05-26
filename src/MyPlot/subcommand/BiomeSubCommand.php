<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\BiomeForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\biome\BiomeRegistry;

class BiomeSubCommand extends SubCommand
{
	public CONST BIOMES = ["PLAINS" => BiomeIds::PLAINS, "DESERT" => BiomeIds::DESERT, "MOUNTAINS" => BiomeIds::MOUNTAINS, "FOREST" => BiomeIds::FOREST, "TAIGA" => BiomeIds::TAIGA, "SWAMP" => BiomeIds::SWAMP, "NETHER" => BiomeIds::HELL, "HELL" => BiomeIds::HELL, "ICE_PLAINS" => BiomeIds::ICE_PLAINS];

	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.biome");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(empty($args)) {
			$biomes = TextFormat::WHITE . implode(", ", array_keys(self::BIOMES));
			$sender->sendMessage($this->translateString("biome.possible", [$biomes]));
			return true;
		}
		$player = $sender->getServer()->getPlayer($sender->getName());
		$biome = strtoupper($args[0]);
		$plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.biome")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		if(is_numeric($biome)) {
			$biome = (int) $biome;
			if($biome > 27 or $biome < 0) {
				$sender->sendMessage(TextFormat::RED . $this->translateString("biome.invalid"));
				$biomes = implode(", ", array_keys(self::BIOMES));
				$sender->sendMessage(TextFormat::RED . $this->translateString("biome.possible", [$biomes]));
				return true;
			}
			$biome = BiomeRegistry::getInstance()->getBiome($biome);
		}else{
			$biome = ($biome === "NETHER" ? "HELL" : $biome);
			$biome = ($biome === "ICE PLAINS" ? "ICE_PLAINS" : $biome);
			if(!defined(BiomeIds::class."::".$biome)) {
				$sender->sendMessage(TextFormat::RED . $this->translateString("biome.invalid"));
				$biomes = implode(", ", array_keys(self::BIOMES));
				$sender->sendMessage(TextFormat::RED . $this->translateString("biome.possible", [$biomes]));
				return true;
			}
			$biome = BiomeRegistry::getInstance()->getBiome(constant(BiomeIds::class."::".$biome));
		}
		if($this->getPlugin()->setPlotBiome($plot, $biome)) {
			$sender->sendMessage($this->translateString("biome.success", [$biome->getName()]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($this->getPlugin()->getPlotByPosition($player->getPosition()) instanceof Plot)
			return new BiomeForm(array_keys(self::BIOMES));
		return null;
	}
}