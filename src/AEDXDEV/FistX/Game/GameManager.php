<?php

namespace AEDXDEV\FistX\Game;

use AEDXDEV\FistX\Main;

use pocketmine\utils\Config;
use pocketmine\player\Player;
use pocketmine\world\World;

class GameManager {
  private $plugin;
	
  public function __construct(Main $plugin) {
    $this->plugin = $plugin;
  }
  
  public function AddGame($name, $world) {
    $data = new Config($this->plugin->getDataFolder() . "games/" . $name . ".yml", Config::YAML, [
      "Name" => $name,
      "World" => $world,
      "Lobby" => "x:y:z",
      "Respawn" => "x:y:x"
    ]);
    $this->plugin->games[$name] = new Game($this->plugin, $name, $world);
  }
  
  public function setLobby($name, $xyz) {
    if (in_array($name, $this->plugin->games)) {
      $game = $this->plugin->games[$name];
      $game->setLobby($xyz);
    }
  }
  
  public function setRespawn($name, $xyz) {
    if (in_array($name, $this->plugin->games)) {
      $game = $this->plugin->games[$name];
      $game->setRespawn($xyz);
    }
  }
  
  public function DeleteGame($name) {
    unlink($this->plugin->getDataFolder() . "games/" . $name . "");
    rmdir($this->plugin->getDataFolder() . "games/" . $name);
    unset($this->plugin->games[$name]);
  }
  
  public function JoinGame(Player $player, $gameName) {
    if (count($this->plugin->getGames()) == 0) {
      $player->sendMessage("§cNo game found!");
      return false;
    }
    foreach ($this->plugin->games as $name => $game) {
      $pname = $player->getName();
      if ($name == $gameName) {
        if (isset($game->players[$pname])){
          $player->sendMessage("§cYou are already in game!");
      	  return false;
        }
        $lobby = $game->getLobby();
		    if ($lobby == "x:y:z") {
	        if($player->hasPermission("fistx.admin")){
	        	$player->sendMessage("§cPlease set lobby position, Usage: /fist setlobby");
		      	return false;
	        }
		    }
		    $respawn = $game->getRespawn();
		    if ($respawn == "x:y:z") {
		      if($player->hasPermission("fist.admin")){
	    	    $player->sendMessage("§cPlease set respawn position, Usage: /fist setrespawn");
		  	    return false;
		      }
		    }
		    $game->JoinGame($player);
      }
    }
  }
  
  public function JoinRandomGame(Player $player): bool{
    if (count($this->plugin->getGames()) == 0) {
      $player->sendMessage("§cNo arenas found!");
      return false;
    }
    foreach ($this->plugin->games as $name => $game) {
      $pname = $player->getName();
      if (isset($game->players[$pname])){
        $player->sendMessage("§cYou are already in game!");
      	return false;
      }
      $games = [];
		  $games[] = $name;
		  $rand = array_rand($games);
      $this->JoinGame($player, $rand);
      return true;
    }
		return false;
	}
  
  public function QuitGame(Player $player, $gameName) {
    foreach ($this->plugin->games as $name => $game) {
      $pname = $player->getName();
      if ($name == $gameName) {
        if(!isset($game->players[$pname])) {
          return false;
        }
      }
      $game->QuitGame($player);
    }
  }
  
  public function setGameName($name, $new){
    if (in_array($name, $this->plugin->games)) {
      $game = new Config($this->plugin->getDataFolder() . "games/" . $name . ".yml", Config::YAML);
      $world = $game->get("world");
      $lobby = $game->get("lobby");
      $respawn = $game->get("respawn");
      $data = new Config($this->plugin->getDataFolder() . "games/" . $new . ".yml", Config::YAML, [
          "Name" => $new,
          "World" => $world,
          "Lobby" => $lobby,
          "Respawn" => $respawn
        ]);
      unlink($this->plugin->getDataFolder() . "games/" . $name . ".yml");
      rmdir($this->plugin->getDataFolder() . "games/" . $name);
      unset($this->plugin->games[$name]);
      $this->plugin->games[$new] = new Game($this->plugin, $new, $world);
    }
  }
  
  public function inGame(Player $player): bool {
    $pname = $player->getName();
    foreach ($this->plugin->games as $name => $game) {
      if (!isset($game->players[$pname])){
        return false;
      }
    }
    return true;
  }
  
  public function getGame(Player $player) {
    $pname = $player->getName();
    foreach ($this->plugin->games as $name => $game) {
      if (isset($game->players[$pname])){
        return $game;
      }
      return false;
    }
  }
}
