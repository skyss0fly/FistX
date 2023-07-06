<?php

namespace AEDXDEV\FistX\Game;

use pocketmine\world\Position;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\player\GameMode;
use pocketmine\utils\Config;
use pocketmine\world\World;

use AEDXDEV\FistX\Main;

class Game {
  
  public $plugin;
  public $GameName;
  public $World;
  public $Lobby;
  public $Respawn;
  // array
  public array $players = [];
  public array $protect = [];
  
  public function __construct(Main $plugin, $GameName, $World){
    $this->plugin = $plugin;
    $this->GameName = $GameName;
    $this->World = $World;
    if (!is_file($this->plugin->getDataFolder() . "games/" . $GameName . ".yml")) {
      (new Config($this->plugin->getDataFolder() . "games/" . $GameName . ".yml", Config::YAML, [
				"Name" => $GameName,
				"World" => $World,
				"Lobby" => $this->Lobby,
				"Respawn" => $this->Respawn
			]));
    }
  }
  
  public function Name() {
    return $this->GameName;
  }
  
  public function getWorld() {
    return $this->World;
  }
  
  public function setLobby($xyz) {
    $data = new Config($this->plugin->getDataFolder() . "games/" . $GameName . ".yml", Config::YAML);
    $data->set("Lobby", $xyz);
    $data->save();
    $this->Lobby = $xyz;
  }
  
  public function getLobby() {
    return $this->Lobby;
  }
  
  public function setRespawn($xyz) {
    $data = new Config($this->plugin->getDataFolder() . "games/" . $GameName . ".yml", Config::YAML);
    $data->set("Respawn", $xyz);
    $data->save();
    $this->Respawn = $xyz;
  }
  
  public function getRespawn() {
    return $this->Respawn;
  }
  
  public function getPlayers(): array{
		return $this->players;
	}
	
	public function isProtected(Player $player): bool {
	  $name = $player->getName();
	  if (isset($this->protect[$name])) {
	    return true;
	  }
	  return false;
	}
	
	public function Clear(Player $player){
		$player->setHealth(20);
		$player->getHungerManager()->setFood(20);
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getEffects()->clear();
	}
	
	public function JoinGame(Player $player): bool{
		if(isset($this->players[$player->getName()]))return false;
		$lobby = explode(":", $this->getLobby());
		$player->teleport(new Position($lobby[0], $lobby[1], $lobby[2], $this->getWorld()), $lobby[3], $lobby[4]);
		$player->setGamemode(GameMode::ADVENTURE());
		$this->Clear($player);
		$player->getInventory()->setItem(0, VanillaItems::STEAK()->setCount(64));
		$this->players[] = $player;
		$this->protect[$player->getName()] = 3;
		$player->sendMessage("§eYou are now protected 3 seconds");
		foreach ($this->players as $name){
	    $player = $this->plugin->getServer()->getPlayerExact($name);
			$player->sendMessage("§b" . $name . " §ajoined to Fist!");
			return true;
		}
	}
	
	public function Respawn(Player $player){
	  $player->setGamemode(GameMode::ADVENTURE());
		$this->Clear($player);
		$player->getInventory()->setItem(0, VanillaItems::STEAK()->setCount(64));
		$respawn = explode(":", $this->getRespawn());
		$player->teleport(new Position($respawn[0], $respawn[1], $respawn[2], $this->getWorld()), $respawn[3], $respawn[4]);
		$cfg = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
		$this->protect[$player->getName()] = 3;
		$player->sendMessage("You are now protected 3 seconds");
	}
	
	public function QuitGame(Player $player, $event = false): bool{
		if(!isset($this->players[$player->getName()]))return false;
		unset($this->players[$player->getName()]);
		if(isset($this->protect[$player->getName()])){
		  unset($this->protect[$player->getName()]);
		}
		if ($event) {
		} else {
		  $player->teleport($this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
		}
		$this->Clear($player);
		foreach ($this->players as $name){
		  $player = $this->plugin->getServer()->getPlayerExact($name);
			$player->sendMessage("§b" . $name . " §cquit Fist!");
		}
		return true;
	}
	
	public function tick(){
		$scoreboard = new GameScoreBoard($this->plugin);
		foreach ($this->players as $name){
		  $player = $this->plugin->getServer()->getPlayerExact($name);
			$cfg = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
			$scoreboard->new($player, "fist", $scoreboard->scoreboardsLines[$scoreboard->ScoreBoard->scoreboardsLine]);
			$scoreboard->setLine($player, 1, "");
			$scoreboard->setLine($player, 2, " §bPlayers: §a" . count($this->getPlayers()) . "  ");
			$scoreboard->setLine($player, 3, " ");
			$scoreboard->setLine($player, 4, " §bMap: §a" . $this->GameName() . "  ");
			$scoreboard->setLine($player, 5, "  ");
			$scoreboard->setLine($player, 6, " §bKills: §a" . $this->plugin->getKills($player) . " ");
			$scoreboard->setLine($player, 7, " §bDeaths: §a" . $this->plugin->getDeaths($player) . " ");
			$scoreboard->setLine($player, 8, "   ");
			$scoreboard->setLine($player, 9, " " . $cfg->get("scoreboardIp", "§eplay.example.net") . " ");
		}
		
		if($scoreboard->scoreboardsLine == (count($scoreboard->scoreboardsLines) - 1)){
			$scoreboard->scoreboardsLine = 0;
		} else {
			++$scoreboard->scoreboardsLine;
		}
		
		foreach ($this->protect as $name => $time){
			if($time == 0){
				unset($this->protect[$name]);
			} else {
				$this->protect[$name]--;
			}
		}
	}
}
