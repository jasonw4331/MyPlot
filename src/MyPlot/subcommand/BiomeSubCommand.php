<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\level\generator\biome\Biome;

class BiomeSubCommand extends SubCommand
{
    private $biomes = [
        "PLAINS" => Biome::PLAINS,
        "DESERT" => Biome::DESERT,
        "MOUNTAINS" => Biome::MOUNTAINS,
        "FOREST" => Biome::FOREST,
        "TAIGA" => Biome::TAIGA,
        "SWAMP" => Biome::SWAMP,
        "NETHER" => Biome::HELL,
        "HELL" => Biome::HELL,
        "ICE" => Biome::ICE_PLAINS
    ];

    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.biome");
    }

    public function execute(CommandSender $sender, array $args) {
        if (count($args) === 0) {
            $biomes = TextFormat::WHITE . implode(", ", array_keys($this->biomes));
            $sender->sendMessage($this->translateString("biome.possible", [$biomes]));
            return true;
        } elseif (count($args) !== 1) {
            return false;
        }
        $player = $sender->getServer()->getPlayer($sender->getName());
        $biome = strtoupper($args[0]);
        $plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
            return true;
        }
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.biome")) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
            return true;
        }
        if (!isset($this->biomes[$biome])) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("biome.invalid"));
            $biomes = implode(", ", array_keys($this->biomes));
            $sender->sendMessage(TextFormat::RED . $this->translateString("biome.possible", [$biomes]));
            return true;
        }
        $biome = Biome::getBiome($this->biomes[$biome]);
        if ($this->getPlugin()->setPlotBiome($plot, $biome)) {
            $sender->sendMessage($this->translateString("biome.success", [$biome->getName()]));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
        }
        return true;
    }
}