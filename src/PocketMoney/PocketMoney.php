<?php

namespace PocketMoney;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use PocketMoney\Error\SimpleError;
use PocketMoney\PocketMoneyAPI;
use PocketMoney\constants\PlayerType;

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
						$sender->sendMessage("/money help( or /money )");
						$sender->sendMessage("/money view <account>");
						$sender->sendMessage("/money create <account>");
						$sender->sendMessage("/money hide <account>");
						$sender->sendMessage("/money unhide <account>");
						$sender->sendMessage("/money set <target> <amount>");
						$sender->sendMessage("/money grant <target> <amount>");
						$sender->sendMessage("/money top <amount>");
						$sender->sendMessage("/money stat");
						break;
					
					case "view":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("Usage: /money view <account>");
							break;
						}

                        $money = PocketMoneyAPI::getAPI()->getMoney($account);
                        $type = PocketMoneyAPI::getAPI()->getType($account);
						if ($money instanceof SimpleError) {
							$sender->sendMessage($money->getDescription());
							break;
						}
                        if ($type instanceof SimpleError) {
                            $sender->sendMessage($type->getDescription());
                            break;
                        }
						$type =  ($type === PlayerType::Player) ? "Player" : "Non-player";
						$sender->sendMessage("[PocketMoney] \"$account\" money:$money PM, type:$type");
						break;

					case "create":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("Usage: /money create <account>");
							break;
						}
                        if (($err = PocketMoneyAPI::getAPI()->createAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage(" \"{$account}\" was created");
						break;

					case "hide":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("Usage: /money hide <account>");
							break;
						}
                        if (($err = PocketMoneyAPI::getAPI()->hideAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
						$sender->sendMessage("\"{$account}\" was hidden");
						break;

					case "unhide":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("Usage: /money unhide <account>");
							break;
						}
                        if (($err = PocketMoneyAPI::getAPI()->unhideAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("\"{$account}\" was hidden");
						break;

					case "set":
						$target = array_shift($args);						
						$amount = array_shift($args);
						if (is_null($target) or is_null($amount)) {
							$sender->sendMessage("Usage: /money set <target> <amount>");
							break;
						}
                        if (($err = PocketMoneyAPI::getAPI()->setMoney($target, $amount)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
						$sender->sendMessage("[PocketMoney][set] Done!");
						$this->getServer()->getPlayer($target)->sendMessage("Your money was changed to $amount PM by admin");
						break;

					case "grant":
						$target = array_shift($args);						
						$amount = array_shift($args);
						if (is_null($target) or is_null($amount)) {
							$sender->sendMessage("Usage: /money grant <target> <amount>");
							break;
						}
                        if (($err = PocketMoneyAPI::getAPI()->grantMoney($target, $amount)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("[grant] Done!");
                        $this->getServer()->getPlayer($target)->sendMessage("Your money was changed to $amount PM by admin");
                        break;
						
					case "top":
						$amount = array_shift($args);
                        if (is_null($amount)) {
                            $sender->sendMessage("Usage: /money top <amount>");
							break;
						}
                        $sender->sendMessage("[PocketMoney] Millionaires");
                        $sender->sendMessage("===========================");
                        $rank = 1;
						foreach (PocketMoneyAPI::getAPI()->getRanking($amount) as $name => $money) {
                            $sender->sendMessage("#$rank : $name $money PM");
							$rank++;
						}
						break;
					case "stat":
						$totalMoney = PocketMoneyAPI::getAPI()->getTotalMoney();
                        $accountNum = PocketMoneyAPI::getAPI()->getNumberOfAccount();
						$avr = floor($totalMoney / $accountNum);
                        $sender->sendMessage("[PocketMoney] Circulation:$totalMoney Average:$avr Accounts:$accountNum");
						break;

					default:
                        $sender->sendMessage("\"/money $subCommand\" dose not exist");
						break;
				}
				return true;
		}
	}

	private function onCommandByUser(CommandSender $sender, Command $command, $label, array $args)
	{

	}
}