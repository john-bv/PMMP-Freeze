<?php

declare(strict_types=1);

namespace Bavfalcon9\freeze;
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
	private $config;
	private $msgs = array(
		"hit" => false,
		"attack" => false,
		"noperm" => false,
		"frozen" => false,
		"immune" => false,
		"player" => false,
		"title" => false,
		"actionbar" => false,
		"frozen-msg" => false
	);

	public function onEnable() :void {
		$this->getLogger()->info("Enabled Freeze. Made by: Bavfalcon9");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		//Initialize Config
		$this->saveResource("config.yml");
		$this->saveDefaultConfig();
		$this->freeze = $this->getConfig()->get("format");
		$this->freeze_tag = $this->getConfig()->get("format-tag");
		$keys = array_keys($this->msgs);
		foreach(array_keys($this->msgs) as $msg) {
			if($this->getConfig()->exists("error-".$msg) !== false) {
				$this->msgs[$msg] = $this->getConfig()->get("error-".$msg);
			}
		}
	}
	public function onMove(PlayerMoveEvent $event) : void {
		$player = $event->getPlayer();
		if(in_array($player->getName(), $this->frozen)) {
			$event->setCancelled(true);
			if($this->getConfig()->get("action-msg") === true && $this->msgs["title"] !== false) $player->addActionBarMessage($this->msgs["title"]);
			if($this->getConfig()->get("title-msg") === true && $this->msgs["actionbar"] !== false) $player->addTitle($this->msgs["actionbar"]);
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
						if($this->msgs["attack"] !== false) $damager->sendMessage($this->freeze . $this->msgs["attack"]);
						//TextFormat::RED."You can't hit frozen Players."
					}
				}
			}
		}
		if($damager instanceof Player) {
			if(in_array($damager->getName(), $this->frozen)) {
				if($this->getConfig()->get("attack-frozen") === false) {
					$event->setCancelled(true);
					if($this->msgs["hit"] !== false) $damager->sendMessage($this->freeze . $this->msgs["hit"]);
				}
			}
		}

	}
	public function onJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();
		if(in_array($player->getName(), $this->frozen)) {
			$player->setImmobile(true);
			if($this->getConfig()->get("frozen-tag") === true) $player->setNameTag($this->freeze_tag.$player->getNametag()); //fix bug with purechat/perms
			if($this->msgs["onjoin"] !== false) $player->sendMessage($this->freeze . $this->msgs["onjoin"]);
		}
	}
	public function onQuit(PlayerQuitEvent $e) {
		$player = $e->getPlayer();
		if(in_array($player->getName(), $this->frozen)) {
			if(!$this->getConfig()->get("autoban")) return false;
			else {
				$this->getServer()->getNameBans()->addBan($player->getName(), $this->filterVar('autoban-reason', $player), null, "CONSOLE");
				$this->getServer()->broadcastMessage($this->freeze . $this->filterVar('autoban-msg', $player));
			}
		}
	}
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
		if($command->getName() === "freeze") {
			if($sender->hasPermission("freeze.command") === false && $sender->hasPermission("freeze") === false && $sender->isOp() === false) {
				if($this->msgs["noperm"] !== false) $sender->sendMessage($this->msgs["noperm"]);
				return false;			
			}
			if(!isset($args[0])) {
				$sender->sendMessage($this->freeze . TextFormat::RED . "Provide a player"); // ADD SUPPORT FOR CMDS
				return false;
			}

			if($this->getServer()->getPlayer($args[0]) === null) {
				$sender->sendMessage($this->freeze . TextFormat::RED . "Could not find player: §e".$args[0]); // ADD SUPPORT FOR CMDS
				return false;	
			}

			$player = $this->getServer()->getPlayer($args[0]);
			if($player->hasPermission("freeze.immune") || $player->hasPermission("freeze") || $player->isOp()) {
				if($this->msgs["immune"] !== false) $sender->sendMessage($this->freeze . $this->msgs["immune"]);
				return false;
			}
			if(in_array($player->getName(), $this->frozen)) {
				if($this->msgs["frozen"] !== false) $sender->sendMessage($this->freeze . $this->msgs["frozen"]);
				return false;		
			} else {
				array_push($this->frozen, $player->getName());
				$player->setImmobile(true);
				$this->getServer()->broadcastMessage($this->freeze ."§e" . $player->getName() . "§r is now frozen."); // Adding to config soon.
				if($this->getConfig()->get("frozen-tag") === true) $player->setNameTag($this->freeze_tag.$player->getNametag());
				if($this->getConfig()->get("action-msg") === true && $this->msgs["title"] !== false) $player->addActionBarMessage($this->msgs["title"]);
				if($this->getConfig()->get("title-msg") === true && $this->msgs["actionbar"] !== false) $player->addTitle($this->msgs["actionbar"]);
				if($this->getConfig()->get("frozen-msg") === true && $this->msgs["frozen-msg"] !== false) $player->sendMessage($this->freeze . $this->msgs["frozen-msg"]);
				return true;
			}
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

			if($this->getServer()->getPlayer($args[0]) === null) {
				$sender->sendMessage($this->freeze . TextFormat::RED."Could not find player: §e".$args[0]);
				return false;	
			}

			$player = $this->getServer()->getPlayer($args[0]);
			if(in_array($player->getName(), $this->frozen)) {
				array_splice($this->frozen, array_search($player->getName(), $this->frozen), 1);
				$player->sendMessage($this->freeze . TextFormat::GREEN."You are no longer frozen.");
				$player->setImmobile(false);
				$player->addTitle(TextFormat::GREEN . "You are unfrozen!");
				$player->setNameTag(str_replace($this->freeze_tag, "", $player->getNameTag()));
				$this->getServer()->broadcastMessage($this->freeze . "§e" . $player->getName() . "§r is no longer frozen.");
				return true;		
			} else {
				$sender->sendMessage($this->freeze . "§e" . $player->getName() . "§r is not frozen!");
				return true;
			}
			return true;
		}
		return true;
	}
	// findplayer deprecated due to it's uselessness

	public function onPlayerCommand(PlayerCommandPreprocessEvent $event) : bool {
        if ($event->isCancelled()) return true;
		$player = $event->getPlayer();
		$message = $event->getMessage();
		$isDm = false;
		$cmds = ["/msg", "/w", "/tell", "/whisper", "/message", "/pm", "/m"];
		foreach($cmds as $cmd) {
			if(!in_array($player->getName(), $this->frozen)) continue;
			if(strpos($message, $cmd) !== false) $isDm = true;
		}
		if($this->getConfig()->get("dms-frozen") === false) {
			$cmds = ["/msg", "/w", "/tell", "/whisper", "/message", "/pm", "/m"];
			foreach($cmds as $cmd) {
				if(!in_array($player->getName(), $this->frozen)) continue;
				if(strpos($message, $cmd) !== false) {
					$event->setCancelled(true);
					$player->sendMessage($this->freeze . TextFormat::RED."You can not private message while frozen.");
					break;
					return true;
				}
			}
		}
		if($this->getConfig()->get("commands-frozen") === false && $isDm === false) {
			if(strpos($message, "/") !== false) {
				if(!in_array($player->getName(), $this->frozen)) return false;
					$event->setCancelled(true);
					$player->addActionBarMessage(TextFormat::RED . "You are Frozen!");
					$player->sendMessage($this->freeze . TextFormat::RED."You can not use commands while frozen.");
			}
		}
		return true;
	}
	private function filterVar(String $str, $p) {
		$configType = $this->getConfig()->get($str);
		$fin = str_replace("%player%", $p->getName(), $configType);
		return $fin;
	}

	public function onDisable() {
		$this->getLogger()->info("Freeze Plugin Disabled");
	}

}

