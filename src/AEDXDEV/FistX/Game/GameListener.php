<?php

namespace AEDXDEV\FistX\Game;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\utils\Config;

use pocketmine\player\Player;
use pocketmine\player\GameMode;

use AEDXDEV\FistX\Main;

class GameListener implements Listener {
	public $plugin;
	
	public function __construct(Main $plugin) {
    $this->plugin = $plugin;
	}
	
  public function onDrop(PlayerDropItemEvent $event){
		$player = $event->getPlayer();
		if (count($this->plugin->getGames()) == 0)return false;
		if($player instanceof Player){
		  if ($this->plugin->GameManager->inGame($player)) {
		    $event->cancel();
		  }
		}
	}
	
	public function onHunger(PlayerExhaustEvent $event){
	  $player = $event->getPlayer();
	  if (count($this->plugin->getGames()) == 0)return false;
		if($player instanceof Player){
		  if ($this->plugin->GameManager->inGame($player)) {
		  }
		}
	}
	
	public function onQuit(PlayerQuitEvent $event){
	  $player = $event->getPlayer();
	  if (count($this->plugin->getGames()) == 0)return false;
		if($player instanceof Player){
		  if ($this->plugin->GameManager->inGame($player)) {
		    $this->plugin->GameManager->QuitPlayer($player, true);
		  }
		}
	}
	
	public function onTeleport(EntityTeleportEvent $event){
	  $player = $event->getEntity();
	  if (count($this->plugin->getGames()) == 0)return false;
		$from = $event->getFrom();
		$to = $event->getTo();
		if($player instanceof Player){
		  if ($this->plugin->GameManager->inGame($player) && $from->getWorld() !== $to->getWorld()) {
		    $this->plugin->GameManager->QuitPlayer($player);
		  }
		}
	}
	
	public function onPlace(BlockPlaceEvent $event){
	  $player = $event->getPlayer();
	  if (count($this->plugin->getGames()) == 0)return false;
		if($player instanceof Player){
		  if ($this->plugin->GameManager->inGame($player)) {
		    $event->cancel();
		  }
		}
	}
	
	public function onBreak(BlockBreakEvent $event){
	  $player = $event->getPlayer();
	  if (count($this->plugin->getGames()) == 0)return false;
		if($player instanceof Player){
		  if ($this->plugin->GameManager->inGame($player)) {
		    $event->cancel();
		  }
		}
	}
	
	public function onDamage(EntityDamageEvent $event): void{
	  $player = $event->getEntity();
	  if (count($this->plugin->getGames()) == 0)return;
    if($player instanceof Player){
			if ($this->plugin->GameManager->inGame($player)) {
				if($player->getHealth() <= $event->getFinalDamage()){
					$this->Kill($player);
					$event->cancel();
					return;
				}
				if($event instanceof EntityDamageByEntityEvent && ($damager = $event->getDamager()) instanceof Player){
					if($this->plugin->GameManager->getGame($player)->isProtected($player)){
						$event->cancel();
					}
				}
			}
		}
	}
	
  public function Kill(Player $player): void{
		$message = null;
		$event = $player->getLastDamageCause();
		if($event == null)return;
		if(!is_int($event->getCause()))return;
		if (count($this->plugin->getGames()) == 0)return;
		$this->Items($player);
		$this->plugin->addDeath($player);
		$cfg = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
		switch ($event->getCause()){
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
				$damager = $event instanceof EntityDamageByEntityEvent ? $event->getDamager() : null;
				if($damager !== null && $damager instanceof Player){
					$message = str_replace(["{PLAYER}", "{KILLER}"], [$player->getName(), $damager->getName()], $cfg->get("death-attack-message"));
					$this->plugin->addKill($damager);
					$damager->sendPopup("§a+1 §eKill");
					$this->Items($damager);
				}
			break;
			case EntityDamageEvent::CAUSE_VOID:
				$message = str_replace(["{PLAYER}"], [$player->getName()], $cfg->get("death-void-message"));
			break;
		}
		if($message !== null){
			foreach ($this->plugin->GameManager->getGame()->players as $player_){
			  $player = $this->plugin->getServer()->getPlayerExact($player_);
				$player->sendMessage($message);
			}
		}
	  $this->plugin->games->Respawn($player);
	}
	
	public function Items(Player $player) {
	  $player->setGamemode(GameMode::ADVENTURE());
		$player->setHealth(20);
		$player->getHungerManager()->setFood(20);
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getEffects()->clear();
	}
}
