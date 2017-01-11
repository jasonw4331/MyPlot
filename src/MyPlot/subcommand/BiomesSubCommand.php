<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\level\generator\biome\Biome;

class BiomesSubCommand extends SubCommand {
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
        $biomes = TextFormat::WHITE . implode(", ", array_keys($this->biomes));
        $sender->sendMessage($this->translateString("biome.possible", [$biomes]));
        return true;
    }
}