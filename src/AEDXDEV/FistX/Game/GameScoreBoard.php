<?php

namespace AEDXDEV\FistX\Game;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use AEDXDEV\FistX\Main;

class GameScoreBoard {
	private $plugin;
	/** @var string[] */
	public $scoreboards = [];
	/** @var int */
	public $scoreboardsLine = 0;
	
	public $scoreboardsLines = [
		0 => "§l§eFIST",
		1 => "§l§fFI§eST",
		2 => "§l§eF§fI§eS§fT",
		3 => "§l§eFI§fST",
		4 => "§l§fFIST"
	];

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
	}

	public function new(Player $player, string $objectiveName, string $displayName): void{
		if(isset($this->scoreboards[$player->getName()])){
			$this->remove($player);
		}
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = "sidebar";
		$pk->objectiveName = $objectiveName;
		$pk->displayName = $displayName;
		$pk->criteriaName = "dummy";
		$pk->sortOrder = 0;
		$player->getNetworkSession()->sendDataPacket($pk);
		$this->scoreboards[$player->getName()] = $objectiveName;
	}

	public function remove(Player $player): void{
		$objectiveName = $this->getObjectiveName($player) ?? "fist";
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $objectiveName;
		$player->getNetworkSession()->sendDataPacket($pk);
		unset($this->scoreboards[$player->getName()]);
	}

	public function setLine(Player $player, int $score, string $message): void{
		if(!isset($this->scoreboards[$player->getName()])){
			$this->plugin->getLogger()->error("Cannot set a score to a player with no scoreboard");
			return;
		}
		if($score > 15 || $score < 1){
			$this->plugin->getLogger()->error("Score must be between the value of 1-15. $score out of range");
			return;
		}
		$objectiveName = $this->getObjectiveName($player) ?? "fist";
		$entry = new ScorePacketEntry();
		$entry->objectiveName = $objectiveName;
		$entry->type = $entry::TYPE_FAKE_PLAYER;
		$entry->customName = $message;
		$entry->score = $score;
		$entry->scoreboardId = $score;
		$pk = new SetScorePacket();
		$pk->type = $pk::TYPE_CHANGE;
		$pk->entries[] = $entry;
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public function getObjectiveName(Player $player): ?string{
		return isset($this->scoreboards[$player->getName()]) ? $this->scoreboards[$player->getName()] : null;
	}
}
