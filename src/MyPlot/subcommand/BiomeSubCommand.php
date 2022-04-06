<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use MyPlot\forms\subforms\BiomeForm;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\biome\BiomeRegistry;
use SOFe\AwaitGenerator\Await;

class BiomeSubCommand extends SubCommand{
	public const BIOMES = ["PLAINS" => BiomeIds::PLAINS, "DESERT" => BiomeIds::DESERT, "MOUNTAINS" => BiomeIds::ICE_MOUNTAINS, "FOREST" => BiomeIds::FOREST, "TAIGA" => BiomeIds::TAIGA, "SWAMP" => BiomeIds::SWAMPLAND, "NETHER" => BiomeIds::HELL, "HELL" => BiomeIds::HELL, "ICE_PLAINS" => BiomeIds::ICE_PLAINS];

	public function canUse(CommandSender $sender) : bool{
		if(!$sender->hasPermission("myplot.command.biome")){
			return false;
		}
		if($sender instanceof Player){
			$pos = $sender->getPosition();
			$plotLevel = $this->internalAPI->getLevelSettings($pos->getWorld()->getFolderName());
			if($this->internalAPI->getPlotFast($pos->x, $pos->z, $plotLevel) === null){
				return false;
			}
		}

		return true;
	}

	/**
	 * @param Player   $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool{
		Await::f2c(
			function() use ($sender, $args) : \Generator{
				if(count($args) === 0){
					$biomes = TextFormat::WHITE . implode(", ", array_keys(self::BIOMES));
					$sender->sendMessage($this->translateString("biome.possible", [$biomes]));
					return;
				}
				$player = $sender->getServer()->getPlayerExact($sender->getName());
				if($player === null)
					return;
				$biome = strtoupper($args[0]);
				$plot = yield from $this->internalAPI->generatePlotByPosition($player->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.biome")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				if(is_numeric($biome)){
					$biome = (int) $biome;
					if($biome > 27 or $biome < 0){
						$sender->sendMessage(TextFormat::RED . $this->translateString("biome.invalid"));
						$biomes = implode(", ", array_keys(self::BIOMES));
						$sender->sendMessage(TextFormat::RED . $this->translateString("biome.possible", [$biomes]));
						return;
					}
					$biome = BiomeRegistry::getInstance()->getBiome($biome);
				}else{
					$biome = ($biome === "NETHER" ? "HELL" : $biome);
					$biome = ($biome === "ICE PLAINS" ? "ICE_PLAINS" : $biome);
					if(!defined(BiomeIds::class . "::" . $biome) or !is_int(constant(BiomeIds::class . "::" . $biome))){
						$sender->sendMessage(TextFormat::RED . $this->translateString("biome.invalid"));
						$biomes = implode(", ", array_keys(self::BIOMES));
						$sender->sendMessage(TextFormat::RED . $this->translateString("biome.possible", [$biomes]));
						return;
					}
					$biome = BiomeRegistry::getInstance()->getBiome(constant(BiomeIds::class . "::" . $biome));
				}
				if(yield from $this->internalAPI->generatePlotBiome($plot, $biome)){
					$sender->sendMessage($this->translateString("biome.success", [$biome->getName()]));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}

	public function getFormClass() : ?string{
		return BiomeForm::class;
	}
}