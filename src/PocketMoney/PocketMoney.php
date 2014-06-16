<?php

namespace PocketMoney;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use PocketMoney\PocketMoneyAPI;
use PocketMoney\constants\PlayerType;

class PocketMoney extends PluginBase
{
	private $pocketMoneyAPI;

	public function onLoad()
	{
	}

	public function onEnable()
	{
		$this->pocketMoneyAPI = PocketMoneyAPI::getAPI();
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
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("[PocketMoney] Usage: /money view <account>");
							break;
						}
						if (!$this->config->exists($account)) {
							$sender->sendMessage("[PocketMoney] The account dose not exist");
							break;
						}
						$money = $this->pocketMoneyAPI->getMoney($account);
						$type =  $this->pocketMoneyAPI->getType($account) === PlayerType::Player ? "Player" : "Non-player";
						$sender->sendMessage("[PocketMoney] \"$account\" money:$money PM, type:$type");
						break;

					case "create":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("[PocketMoney] Usage: /money create <account>");
							break;
						}
						if ($this->config->exists($account)) {
							$sender->sendMessage("[PocketMoney] The account already exists");
							break;
						}
						$this->config->set($account, array('money' => POCKETMONEY_DEFAULT_MONEY, 'type' => PlayerType::NonPlayer, 'hide' => false));
						$this->config->save();
						console("[PocketMoney] \"{$account}\" was created");
						break;

					case "hide":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("[PocketMoney] Usage: /money hide <account>");
							break;
						}
						if (!$this->config->exists($account)) {
							$sender->sendMessage("[PocketMoney] The account dose not exist");
							break;
						}
						if ($this->config->get($account)['hide']) {
							$sender->sendMessage("[PocketMoney] The account has already been hidden");
							break;
						}
						if ($this->config->get($account)['type'] !== PlayerType::NonPlayer) {
							$sender->sendMessage("[PocketMoney] You can hide only Non-player account");
							break;
						}
						$this->config->set($account, array_merge($this->config->get($account), array('hide' => true)));
						$this->config->save();
						$sender->sendMessage("[PocketMoney] \"{$account}\" was hidden");
						break;

					case "unhide":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("[PocketMoney] Usage: /money unhide <account>");
							break;
						}
						if (!$this->config->exists($account)) {
							$sender->sendMessage("[PocketMoney] The account dose not exist");
							break;
						}
						if (!$this->config->get($account)['hide']) {
							$sender->sendMessage("[PocketMoney] The account has not been hidden");
							break;
						}
						$this->config->set($account, array_merge($this->config->get($account), array('hide' => false)));
						$this->config->save();
						console("[PocketMoney] \"$account\" was unhidden");
						break;

					case "set":
						$target = array_shift($args);						
						$amount = array_shift($args);
						if (is_null($target) or is_null($amount)) {
							$sender->sendMessage("[PocketMoney] Usage: /money set <target> <amount>");
							break;
						}
						if (!$this->config->exists($target)) {
							$sender->sendMessage("[PocketMoney] The account dose not exist");
							break;
						}
						if (!is_numeric($amount) or $amount < 0) {
							$sender->sendMessage("[PocketMoney] Invalid amount");
							break;
						}
						//TODO
						PocketMoneyAPI::setMoney($target, $amount);
						$sender->sendMessage("[PocketMoney][set] Done!");
						$this->getServer()->getPlayer($target)->sendMessage("[PocketMoney][INFO] Your money was changed to $amount PM by admin");
						$this->config->save();
						break;

					case "grant":
						$target = array_shift($args);						
						$amount = array_shift($args);
						if (is_null($target) or is_null($amount)) {
							$sender->sendMessage("[PocketMoney] Usage: /money grant <target> <amount>");
							break;
						}
						if (!$this->config->exists($target)) {
							$sender->sendMessage("[PocketMoney] The account dose not exist");
							break;
						}
						$targetMoney = $this->config->get($target)['money'];
						if (!is_numeric($amount) or ($targetMoney + $amount) < 0) {
							$sender->sendMessage("[PocketMoney] Invalid amount.");
							break;
						}
						PocketMoneyAPI::grantMoney($target, $amount);
						self::grantMoney($target, $amount);
						console("[PocketMoney][grant] Done!");
						$this->api->chat->sendTo(false, "[PocketMoney][INFO]You are granted $amount PM by admin", $target);
						$this->config->save();
						break;
						
					case "top":
						$amount = isset($args[1]) ? $args[1] : false;
						if ($amount === false) {
							console("[PocketMoney] Usage: /money top <amount>");
							break;
						}
						$temp = array();
						foreach ($this->config->getAll() as $name => $value) {
							if (!$value['hide']) {
								$temp[$name] = $value['money'];
							}
						}
						arsort($temp);
						$i = 1;
						console("[PocketMoney] Millionaires");
						console("===========================");
						foreach ($temp as $name => $money) {
							if ($i > $amount) {
								break;
							}
							console("#$i : $name $money PM");
							$i++;
						}
						break;
					case "stat":
						$total = 0;
						$num = 0;
						foreach ($cfg as $k => $value) {
							$total += $value['money'];
							$num++;
						}
						$avr = floor($total / $num);
						console("[PocketMoney] Circulation:$total Average:$avr Accounts:$num");
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