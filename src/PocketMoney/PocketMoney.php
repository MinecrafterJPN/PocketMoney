<?php

namespace PocketMoney;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class PocketMoney extends PluginBase
{
	public function onLoad()
	{
		
	}

	public function onEnable()
	{
    }

	public function onDisable()
	{
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args)
	{
		if (strtolower($sender->getName()) !== "console") return $this->onCommandByUser($sender, $command, $label, $args);
		switch ($command->getName()) {
			case "money":
				$subCommand = strtolower(array_shift($args));
				switch ($subCommand) {
					case "":
					case "help":
						$sender->sendMessage("[PocketMoney] /money help( or /money )");
						$sender->sendMessage("[PocketMoney] /money view <account>");
						$sender->sendMessage("[PocketMoney] /money create <account>");
						$sender->sendMessage("[PocketMoney] /money hide <account>");
						$sender->sendMessage("[PocketMoney] /money unhide <account>");
						$sender->sendMessage("[PocketMoney] /money set <target> <amount>");
						$sender->sendMessage("[PocketMoney] /money grant <target> <amount>");
						$sender->sendMessage("[PocketMoney] /money top <amount>");
						$sender->sendMessage("[PocketMoney] /money stat");
						break;
					
					case "view":
						
						break;

					default:
						# code...
						break;
				}
				return true;
		}
	}

	private function onCommandByUser(CommandSender $sender, Command $command, $label, array $args)
	{

	}
}