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
        "OCEAN" => Biome::OCEAN,
        "RIVER" => Biome::RIVER,
        "ICE_PLAINS" => Biome::ICE_PLAINS,
        "SMALL_MOUNTAINS" => Biome::SMALL_MOUNTAINS,
        "BIRCH_FOREST" => Biome::BIRCH_FOREST,
    ];

    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.biome");
    }

    public function getUsage() {
        return "<biome>";
    }

    public function getName() {
        return "biome";
    }

    public function getDescription() {
        return "Changes your plot's biome";
    }

    public function getAliases() {
        return [];
    }

    public function execute(CommandSender $sender, array $args) {
        if (count($args) !== 1) {
            return false;
        }
        $player = $sender->getServer()->getPlayer($sender->getName());
        $biome = strtoupper($args[0]);
        $plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . "You are not standing inside a plot");
            return true;
        }
        if ($plot->owner !== $sender->getName()) {
            $sender->sendMessage(TextFormat::RED . "You are not the owner of this plot");
            return true;
        }
        if (!isset($this->biomes[$biome])) {
            $sender->sendMessage(TextFormat::RED . "That biome doesn't exist");
            $biomes = implode(", ", array_keys($this->biomes));
            $sender->sendMessage(TextFormat::RED . "The possible biomes are: $biomes");
            return true;
        }
        $biome = Biome::getBiome($this->biomes[$biome]);
        if ($this->getPlugin()->setPlotBiome($plot, $biome)) {
            $sender->sendMessage(TextFormat::GREEN . "Changed the plot biome");
        } else {
            $sender->sendMessage(TextFormat::RED . "Could not change the plot biome");
        }
        return true;
    }
}
