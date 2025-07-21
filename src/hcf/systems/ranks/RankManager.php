<?php

namespace hcf\systems\ranks;

use hcf\HCF;
use hcf\systems\ranks\commands\SetRankCommand;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class RankManager
{
    private HCF $plugin;
    /** @var Rank[] $ranks */
    private array $ranks = [];
    private Config $playerRanksConfig;
    private Config $ranksConfig;

    public function __construct(HCF $plugin)
    {
        $this->plugin = $plugin;
       HCF::getInstance()->getServer()->getCommandMap()->register("ranks", new SetRankCommand());
       HCF::getInstance()->getServer()->getPluginManager()->registerEvents(new RankEvents(), HCF::getInstance());
        $this->ranksConfig = new Config($plugin->getDataFolder() . "ranks.json", Config::JSON);
        $this->playerRanksConfig = new Config($plugin->getDataFolder() . "player_ranks.json", Config::JSON);
        $this->loadRanks();
    }

    private function loadRanks(): void
    {
        foreach ($this->ranksConfig->getAll() as $name => $data) {
            $this->ranks[$name] = new Rank($name, $data["prefix"], $data["permissions"] ?? []);
        }
        // Default rank if none exist
        if (empty($this->ranks)) {
            $this->addRank(new Rank("Default", "§7[Default]§r"));
            $this->saveRanks();
        }
    }

    private function saveRanks(): void
    {
        $data = [];
        foreach ($this->ranks as $rank) {
            $data[$rank->getName()] = [
                "prefix" => $rank->getPrefix(),
                "permissions" => $rank->getPermissions()
            ];
        }
        $this->ranksConfig->setAll($data);
        $this->ranksConfig->save();
    }

    public function addRank(Rank $rank): void
    {
        $this->ranks[$rank->getName()] = $rank;
        $this->saveRanks();
    }

    public function getRank(string $name): ?Rank
    {
        return $this->ranks[$name] ?? null;
    }

    public function getPlayerRank(Player $player): Rank
    {
        $playerName = $player->getName();
        $playerData = $this->playerRanksConfig->get($playerName, []);

        if (isset($playerData["rank"]) && isset($playerData["expiry"])) {
            $expiry = (int)$playerData["expiry"];
            if ($expiry === -1 || time() < $expiry) {
                $rank = $this->getRank($playerData["rank"]);
                if ($rank !== null) {
                    return $rank;
                }
            }
        }
        return $this->getRank("Default"); // Return default rank if no specific rank or expired
    }

    public function setPlayerRank(Player $player, Rank $rank, string $duration = ""): void
    {
        $playerName = $player->getName();
        $expiry = -1; // -1 means permanent

        if (!empty($duration)) {
            $time = $this->parseDuration($duration);
            if ($time > 0) {
                $expiry = time() + $time;
            }
        }

        $this->playerRanksConfig->set($playerName, [
            "rank" => $rank->getName(),
            "expiry" => $expiry
        ]);
        $this->playerRanksConfig->save();
    }

    private function parseDuration(string $duration): int
    {
        $time = 0;
        preg_match_all('/(\d+)([smhdwya])/', $duration, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $value = (int)$match[1];
            $unit = $match[2];
            switch ($unit) {
                case 's':
                    $time += $value;
                    break;
                case 'm':
                    $time += $value * 60;
                    break;
                case 'h':
                    $time += $value * 3600;
                    break;
                case 'd':
                    $time += $value * 86400;
                    break;
                case 'w':
                    $time += $value * 604800;
                    break;
                case 'a':
                    $time += $value * 31536000;
                    break;
            }
        }
        return $time;
    }

    public function getAllRanks(): array
    {
        return $this->ranks;
    }
}