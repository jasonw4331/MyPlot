<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\level\biome\Biome;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BiomeSubCommand extends SubCommand
{
	/** @var int[] $biomes */
	private $biomes = ["PLAINS" => Biome::PLAINS, "DESERT" => Biome::DESERT, "MOUNTAINS" => Biome::MOUNTAINS, "FOREST" => Biome::FOREST, "TAIGA" => Biome::TAIGA, "SWAMP" => Biome::SWAMP, "NETHER" => Biome::HELL, "HELL" => Biome::HELL, "ICE" => Biome::ICE_PLAINS];

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
			$biomes = TextFormat::WHITE . implode(", ", array_keys($this->biomes));
			$sender->sendMessage($this->translateString("biome.possible", [$biomes]));
			return true;
		}
		$player = $sender->getServer()->getPlayer($sender->getName());
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
				$biomes = implode(", ", array_keys($this->biomes));
				$sender->sendMessage(TextFormat::RED . $this->translateString("biome.possible", [$biomes]));
				return true;
			}
			$biome = Biome::getBiome($biome);
		}else{
			if(!isset($this->biomes[$biome])) {
				$sender->sendMessage(TextFormat::RED . $this->translateString("biome.invalid"));
				$biomes = implode(", ", array_keys($this->biomes));
				$sender->sendMessage(TextFormat::RED . $this->translateString("biome.possible", [$biomes]));
				return true;
			}
			$biome = Biome::getBiome($this->biomes[$biome]);
		}
		if($this->getPlugin()->setPlotBiome($plot, $biome)) {
			$sender->sendMessage($this->translateString("biome.success", [$biome->getName()]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}
}