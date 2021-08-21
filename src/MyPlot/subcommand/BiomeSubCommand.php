<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\BiomeForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\level\biome\Biome;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BiomeSubCommand extends SubCommand
{
	public CONST BIOMES = ["PLAINS" => Biome::PLAINS, "DESERT" => Biome::DESERT, "MOUNTAINS" => Biome::MOUNTAINS, "FOREST" => Biome::FOREST, "TAIGA" => Biome::TAIGA, "SWAMP" => Biome::SWAMP, "NETHER" => Biome::HELL, "HELL" => Biome::HELL, "ICE_PLAINS" => Biome::ICE_PLAINS, "END" => 9];

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
		if(count($args) === 0) {
			$biomes = TextFormat::WHITE . implode(", ", array_keys(self::BIOMES));
			$sender->sendMessage($this->translateString("biome.possible", [$biomes]));
			return true;
		}
		$player = $sender->getServer()->getPlayerExact($sender->getName());
		if($player === null)
			return true;
		$biome = strtoupper($args[0]);
		$plot = $this->getPlugin()->getPlotByPosition($player);
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
			$biome = Biome::getBiome($biome);
		}else{
			$biome = ($biome === "NETHER" ? "HELL" : $biome);
			$biome = ($biome === "ICE PLAINS" ? "ICE_PLAINS" : $biome);
			if(!defined(Biome::class."::".$biome) or !is_int(constant(Biome::class."::".$biome))) {
				$sender->sendMessage(TextFormat::RED . $this->translateString("biome.invalid"));
				$biomes = implode(", ", array_keys(self::BIOMES));
				$sender->sendMessage(TextFormat::RED . $this->translateString("biome.possible", [$biomes]));
				return true;
			}
			$biome = Biome::getBiome(constant(Biome::class."::".$biome));
		}
		if($this->getPlugin()->setPlotBiome($plot, $biome)) {
			$sender->sendMessage($this->translateString("biome.success", [$biome->getName()]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and $this->getPlugin()->getPlotByPosition($player) instanceof Plot)
			return new BiomeForm(array_keys(self::BIOMES));
		return null;
	}
}
