<?php

namespace AEDXDEV\FistX;

use AEDXDEV\FistX\Game\Game;
use AEDXDEV\FistX\Game\GameManager;
use AEDXDEV\FistX\Game\GameListener;
use AEDXDEV\FistX\Game\GameTask;
use AEDXDEV\FistX\Lib\FormsUI\Form;
use AEDXDEV\FistX\Lib\FormsUI\SimpleForm;
use AEDXDEV\FistX\Lib\FormsUI\CustomForm;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class Main extends PluginBase{
  
	/** @var Game[] */
	public $games = [];
	/** @var GameManager */
	public $GameManager;
	
	public function onEnable(): void{
		$this->getServer()->getPluginManager()->registerEvents(new GameListener($this), $this);
		$this->getScheduler()->scheduleRepeatingTask(new GameTask($this), 20);
		$this->GameManager = new GameManager($this);
		$this->initConfig();
		$this->saveDefaultConfig();
		$this->getServer()->getCommandMap()->register("fist", new FistXCommand($this));
    @mkdir($this->getDataFolder() . "games/");
		$this->loadGames();
	}
	
	public function initConfig(){
		if(!is_file($this->getDataFolder() . "config.yml")){
			(new Config($this->getDataFolder() . "config.yml", Config::YAML, [
				"scoreboardIp" => "§eplay.example.net",
				"death-attack-message" => "§e{PLAYER} §fwas killed by §c{KILLER}",
				"death-void-message" => "§c{PLAYER} §ffall into void"
			]));
		} else {
			$cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
			$all = $cfg->getAll();
			foreach ([
				"scoreboardIp",
				"death-attack-message",
				"death-void-message"
			] as $key){
				if(!isset($all[$key])){
					rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config_old.yml");
					(new Config($this->getDataFolder() . "config.yml", Config::YAML, [
						"scoreboardIp" => "§eplay.example.net",
						"death-attack-message" => "§e{PLAYER} §fwas killed by §c{KILLER}",
						"death-void-message" => "§c{PLAYER} §ffall into void"
					]));
					break;
				}
			}
		}
	}
	
	public function loadGames(){
	  if (count($this->getGames()) == 0)return false;
	  foreach (scandir($this->getDataFolder() . "games/") as $game) {
      if (is_file($this->getDataFolder() . "games/" . $game . ".yml")) {
        $data = new Config($this->getDataFolder() . "games/" . $game . ".yml", Config::YAML);
        $this->games[$data->get("name")] = new Game($this, $data->get("name"), $data->get("world"));
			  if(($world = $this->getServer()->getWorldManager()->getWorldByName($data->get("world"))) !== null){
			    $this->getServer()->getWorldManager()->loadWorld($world);
				  $world->setTime(1000);
				  $world->stopTime();
			  }
        unset($data);
      } else {
        return false;
      }
    }
    return true;
	}
	
	public function getGames(): array{
		return $this->games;
	}
	
	public function JoinForm(Player $player) {
	  $form = new SimpleForm(function (Player $player, $data){
      if ($data === null){
        return;
      }
      switch ($data) {
        case 0:
          $this->SelectForm($player);
        break;
        case 1:
          $this->GameManager->JoinRandomGame($player);
        break;
      }
    });
    $form->setTitle("§eFistX");
    $form->setContent("§7is a survival game and you have steaks that you can eat when you are hungry");
    $form->addButton("§aSelect a Game", 0, "textures/ui/selected_hotbar_slot");
    $form->addButton("§aRandom Game", 0, "textures/ui/random_dice");
    $form->addButton("Exit", 0, "textures/ui/cancel");
    $form->sendToPlayer($player);
	}
	
	public function SelectForm(Player $player) {
	  $form = new SimpleForm(function (Player $player, $data){
      if ($data === null){
        return;
      }
      $games = [];
      foreach ($this->games as $name => $game) {
        $games[] = $name;
      }
      $this->GameManager->JoinGame($player, $games[$data]);
    });
    $form->setTitle("§eFistX");
    $form->setContent("§7is a survival game and you have steaks that you can eat when you are hungry");
    foreach ($this->games as $name => $game) {
      $form->addButton("§a" . $name, 0, "textures/ui/selected_hotbar_slot");
    }
    $form->addButton("Exit", 0, "textures/ui/cancel");
    $form->sendToPlayer($player);
	}
	
	public function ManageForm(Player $player) {
    $form = new SimpleForm(function (Player $player, $data){
	    if ($data === null){
	      return;
	    }
	    switch ($data) {
	      case 0:
	        $this->CreateForm($player);
	      break;
	      case 1:
	        $this->EditForm($player);
	      break;
	      case 2:
	        $this->DeleteForm($player);
	      break;
	      case 3:
	        $this->JoinForm($player);
	      break;
	    }
	  });
	  $form->setTitle("§eFistX");
	  $form->setContent("§7is a survival game and you have steaks that you can eat when you are hungry");
	  $form->addButton("§aCreate §bGame", 0, "textures/ui/imagetaggedcornergreenhover");
	  $form->addButton("§7Edit §bGame", 0, "textures/ui/dev_glyph_color");
	  $form->addButton("§cDelete §bGame", 0, "textures/ui/icon_trash");
	  $form->addButton("§aJoin §bGame", 0, "textures/ui/send_icon");
	  $form->addButton("Exit", 0, "textures/ui/cancel");
	  $form->sendToPlayer($player);
	}
	
	public function CreateForm(Player $player) {
    $form = new CustomForm(function(Player $player, $data){
      if ($data === null){
	      return;
	    }
      $this->GameManager->addGame($data[1], $player->getWorld()->getFolderName());
      $player->sendMessage("§aThe Game Has been Created!");
    });
    $form->setTitle("§eFistX");
    $form->addLabel("§aCreate Game");
    $form->addInput("Name", "add name of game");
    $form->sendToPlayer($player);
  }
  
  public function DeleteForm(Player $player) {
    $form = new CustomForm(function(Player $player, $data){
      if ($data === null){
	      return;
	    }
      $this->GameManager->DeleteGame($data[1]);
    });
    $form->setTitle("§eFistX");
    $form->addLabel("§aDelete Game");
    $games = [];
    foreach ($this->games as $name => $game) {
      $games[] = $name;
    }
    $form->addDropDown("Games", $games);
    $form->sendToPlayer($player);
  }
  
  public function EditForm(Player $player) {
    $form = new CustomForm(function(Player $player, $data){
      if ($data === null){
	      return;
	    }
      $this->Edit2Form($player, $data[0]);
    });
    $form->setTitle("§eFistX");
    $form->addLabel("§aEdit Game");
    $games = [];
    foreach ($this->games as $name => $game) {
      $games[] = $name;
    }
    $form->addDropDown("Games", $games);
    $form->sendToPlayer($player);
  }
  
  public function Edit2Form(Player $player, $game) {
    $form = new CustomForm(function(Player $player, $data) use($game) {
      if ($data === null){
	      return;
	    }
      $this->GameManager->setGameName($game, $data[1],);
      });
    $form->setTitle("§eFistX");
    $form->addLabel("§aEdit Game");
    $form->addInput("Name", "Add the new name to the game");
    $form->sendToPlayer($player);
  }
  
  public function addKill(Player $player, int $add = 1){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($player->getName())){
			$tops->set($player->getName(), ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		$p = $tops->get($player->getName());
		$p["kills"] = ($p["kills"] + $add);
		$tops->set($player->getName(), $p);
		$tops->save();
	}
	
	public function addKillByName(string $name, int $add = 1){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($name)){
			$tops->set($name, ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		$p = $tops->get($name);
		$p["kills"] = ($p["kills"] + $add);
		$tops->set($name, $p);
		$tops->save();
	}
	
	public function addDeath(Player $player, int $add = 1){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($player->getName())){
			$tops->set($player->getName(), ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		$p = $tops->get($player->getName());
		$p["deaths"] = ($p["deaths"] + $add);
		$tops->set($player->getName(), $p);
		$tops->save();
	}
	
	public function addDeathByName(string $name, int $add = 1){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($name)){
			$tops->set($name, ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		$p = $tops->get($name);
		$p["deaths"] = ($p["deaths"] + $add);
		$tops->set($name, $p);
		$tops->save();
	}
	
	public function getKills(Player $player){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($player->getName())){
			$tops->set($player->getName(), ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		return $tops->get($player->getName())["kills"];
	}
	
	public function getKillsByName(string $name){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($name)){
			$tops->set($name, ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		return $tops->get($name)["kills"];
	}
	
	public function getDeaths(Player $player){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($player->getName())){
			$tops->set($player->getName(), ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		return $tops->get($player->getName())["deaths"];
	}
	
	public function getDeathsByName(string $name){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($name)){
			$tops->set($name, ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		return $tops->get($name)["deaths"];
	}
}
