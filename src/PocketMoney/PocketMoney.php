<?php

namespace PocketMoney;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class PocketMoney extends PluginBase
{
	private $system;

	public function onLoad()
	{
	}

	public function onEnable()
	{
		$this->system = new Config($this->dataFolder."system.yml", Config::YAML, array("default_money" => 500));
		define(POCKETMONEY_DEFAULT_MONEY, (int)$this->system->get("default_money"));
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
						$money = $this->config->get($account)['money'];
						$type = $this->config->get($account)['type'] === PlayerType::Player ? "Player" : "Non-player";
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