<?php

declare(strict_types=1);

namespace freeze;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerEvent;
use pocketmine\level\Location;

class Main extends PluginBase implements Listener {
	private $frozen = array();
	private $freeze;
	private $freeze_tag;
	public function onEnable() :void {
		$this->getLogger()->info(TextFormat::GREEN . "Enabled Freeze. Made by: Bavfalcon9");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		//Initialize Config
		$this->saveResource("config.yml");
		$this->saveDefaultConfig();
		$this->freeze = $this->getConfig()->get("format");
		$this->freeze_tag = $this->getConfig()->get("format-tag");
	}
	public function onMove(PlayerMoveEvent $event) : void {
		$player = $event->getPlayer();
		if(in_array($player->getName(), $this->frozen)) {
			$event->setCancelled(true);
			$player->addActionBarMessage(TextFormat::RED . "Listen to staff.§l DO NOT LOG OUT");
			$player->addTitle(TextFormat::RED . "You are Frozen!");
			//$player->sendMessage($this->freeze . TextFormat::RED."You are frozen! Please listen to staff instruction to prevent a ban. §c§lDO NOT LOG OUT!§r§c\n - Refusal to ss is a perm ban");
		}
	}
	public function onAttack(EntityDamageByEntityEvent $event) : void {
		$damager = $event->getDamager();
		$entity = $event->getEntity();
		if($entity instanceof Player) {
			if(in_array($entity->getName(), $this->frozen)) {
				if($this->getConfig()->get("attacked-frozen") === false) {
					$event->setCancelled(true);
					if($damager instanceof Player) {
						$damager->sendMessage($this->freeze . TextFormat::RED."You can't hit frozen Players.");
					}
				}
			}
		}
		if($damager instanceof Player) {
			if(in_array($damager->getName(), $this->frozen)) {
				if($this->getConfig()->get("attack-frozen") === false) {
					$event->setCancelled(true);
					$damager->sendMessage($this->freeze . TextFormat::RED."You can't hit players while frozen.");
				}
			}
		}

	}
	public function onJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();
		if(in_array($player->getName(), $this->frozen)) {
			$player->setImmobile(true);
			if($this->getConfig()->get("frozen-tag") === true) $player->setNameTag($this->freeze_tag.$player->getNametag()); //fix bug with purechat/perms
			$player->addActionBarMessage(TextFormat::RED . "You are Frozen!");
			$player->sendMessage($this->freeze . TextFormat::RED . "You are frozen! Please listen to staff instruction to prevent a ban. §c§lDO NOT LOG OUT!§r§c ");
		}
	}
	public function onQuit(PlayerQuitEvent $e) {
		$player = $e->getPlayer();
		if(in_array($player->getName(), $this->frozen)) {
			if(!$this->getConfig()->get("autoban")) return;
			else {
				$this->getServer()->getNameBans()->addBan($player->getName(), $this->filterVar('autoban-reason', $player), null, "CONSOLE");
				$this->getServer()->broadcastMessage($this->freeze . $this->filterVar('autoban-msg', $player));
			}
		}
	}
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
		if($command->getName() === "freeze") {
			if($sender->hasPermission("freeze.command") === false && $sender->hasPermission("freeze") === false && $sender->isOp() === false) {
				$sender->sendMessage($this->freeze . TextFormat::RED . "Missing permissions: freeze or freeze.command");
				return false;			
			}
			if(!isset($args[0])) {
				$sender->sendMessage($this->freeze . TextFormat::RED . "Provide a player");
				return false;
			}

			if($this->findPlayer($args[0]) === false) {
				$sender->sendMessage($this->freeze . TextFormat::RED . "Could not find player: §e".$args[0]);
				return false;	
			}

			$player = $this->findPlayer($args[0]);
			$p = $this->getServer()->getPlayer($player);
			if($p->hasPermission("freeze.immune") || $p->hasPermission("freeze") || $p->isOp()) {
				$sender->sendMessage($this->freeze . TextFormat::RED . "This player is immune.");
				return false;
			}
			if(in_array($player, $this->frozen)) {
				$sender->sendMessage($this->freeze . TextFormat::RED . "This player is already frozen.");
				return false;		
			} else {
				array_push($this->frozen, $player);
				$p->setImmobile(true);
				$this->getServer()->broadcastMessage($this->freeze ."§e" . $player . "§r is now frozen.");
				if($this->getConfig()->get("frozen-tag") === true) $p->setNameTag($this->freeze_tag.$p->getNametag());
				$p->addActionBarMessage(TextFormat::RED . "You are Frozen!");
				$p->sendMessage($this->freeze . TextFormat::RED . "You are frozen! Please listen to staff instruction to prevent a ban. §c§lDO NOT LOG OUT!§r§c ");
				return true;
			}
			return true;
		}
		if($command->getName() === "frozen") {
			if($sender->hasPermission("freeze.command") === false && $sender->hasPermission("freeze") === false && $sender->isOp() === false) {
				$sender->sendMessage($this->freeze . TextFormat::RED . "Missing permissions: freeze or freeze.command");
				return false;			
			}
			$sender->sendMessage($this->freeze."Frozen Users:§e ".implode(", ", $this->frozen));
			return true;
		};
		if($command->getName() === "unfreeze" || $command->getName() === "thaw") {
			if($sender->hasPermission("freeze.command") === false && $sender->hasPermission("freeze") === false && $sender->isOp() === false) {
				$sender->sendMessage($this->freeze . TextFormat::RED . "Missing permissions: freeze or freeze.command");
				return false;			
			}
			if(!isset($args[0])) {
				$sender->sendMessage($this->freeze . TextFormat::RED ."Provide a player");
				return false;
			}

			if($this->findPlayer($args[0]) === false) {
				$sender->sendMessage($this->freeze . TextFormat::RED."Could not find player: §e".$args[0]);
				return false;	
			}

			$player = $this->findPlayer($args[0]);
			$p = $this->getServer()->getPlayer($player);
			if($p->hasPermission("freeze.immune") || $p->hasPermission("freeze") || $p->isOp()) {
				$sender->sendMessage($this->freeze . TextFormat::RED . "This player is immune.");
				return false;
			}
			if(in_array($player, $this->frozen)) {
				array_splice($this->frozen, array_search($player, $this->frozen), 1);
				$p->sendMessage($this->freeze . TextFormat::GREEN."You are no longer frozen.");
				$p->setImmobile(false);
				$p->addTitle(TextFormat::GREEN . "You are unfrozen!");
				$p->setNameTag(str_replace($this->freeze_tag, "", $p->getNameTag()));
				$sender->sendMessage($this->freeze . "§e" . $player . "§r is no longer frozen.");
				return true;		
			} else {
				$sender->sendMessage($this->freeze . "§e" . $player . "§r is not frozen!");
				return true;
			}
			return true;
		}
		return true;
	}
	// Function being deprecated soon due to it's uselessness owo
	public function findPlayer($username) {
		$plyerFull = "";
		foreach($this->getServer()->getOnlinePlayers() as $oplayer) {
			if(strlen($plyerFull) === 0) {
				$name = $oplayer->getName();
				if(strpos(strtolower($name), strtolower($username)) === 0) {
					$plyerFull = $name;
					continue;
				}
			}
		}
		if(strlen($plyerFull) === 0) {
			return false;
		} else {
			return $plyerFull;
		}
		return $plyerFull;
	}
	public function onPlayerCommand(PlayerCommandPreprocessEvent $event) {
        if ($event->isCancelled()) return true;
		$player = $event->getPlayer();
		$message = $event->getMessage();
		if($this->getConfig("commands-frozen") === false) {
			if(strpos($message, "/") !== false) {
				if(in_array($player->getName(), $this->frozen)) {
					$player->addActionBarMessage(TextFormat::RED . "You are Frozen!");
					$player->sendMessage($this->freeze . TextFormat::RED."You can not use commands while frozen.");
					$event->setCancelled(true);
					return true;
				}
			}
		}
	}
	private function filterVar(String $str, $p) {
		$configType = $this->getConfig()->get($str);
		$fin = str_replace("%player%", $p->getName(), $configType);
		return $fin;
	}

	public function onDisable() {
		$this->getLogger()->info(TextFormat::RED . "Freeze Plugin Disabled");
	}

}

