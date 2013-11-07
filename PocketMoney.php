<?php 

/*
 __PocketMine Plugin__
name=PocketMoney
description=PocketMoney introduces economy into your PocketMine world.
version=2.0
author=MinecrafterJPN
class=PocketMoney
apiversion=10
*/

class PocketMoney implements Plugin
{
	private $api, $config;

	const DEFAULT_MONEY = 500;

	public function __construct(ServerAPI $api, $server = false)
	{
		$this->api = $api;
	}

	public function init()
	{
		$this->config = new Config($this->api->plugin->configPath($this) . "config.yml", CONFIG_YAML);
		$this->api->addHandler("player.join", array($this, "eventHandler"));
		$this->api->addHandler("money.handle", array($this, "eventHandler"));
		$this->api->addHandler("money.player.get", array($this, "eventHandler"));
		$this->api->console->register("money", "PocketMoney command", array($this, "commandHandler"));
	}

	public function eventHandler($data, $event)
	{
		switch ($event) {
			case "money.create.account":
				$account = $data['account'];
				if ($this->config->exist($account)) {
					return false;
				}
				$this->config->set($account, array('money' => self::DEFAULT_MONEY));
				$this->config->save();
				return true;
			case "player.join":
				$target = $data->username;
				if (!$this->config->exists($target)) {
					$this->config->set($target, array('money' => self::DEFAULT_MONEY));
					$this->api->chat->broadcast("[PocketMoney] $target has been registered");
					$this->config->save();
				}
				break;
			case "money.handle":
				if(!isset($data['username']) or !isset($data['method']) or !isset($data['amount']) or !is_numeric($data['amount'])) return false;
				$target = $data['username'];
				$method = $data['method'];
				$amount = $data['amount'];
				if (!$this->config->exists($target)) return false;
				switch ($method) {
					case "set":
						if ($amount < 0) {
							return false;
						}
						$this->config->set($target, array('money' => $amount));
						$this->config->save();
						break;
					case "grant":
						$targetMoney = $this->config->get($target)['money'] + $amount;
						if($targetMoney < 0) return false;
						$this->config->set($target, array('money' => $targetMoney));
						$this->config->save();
						break;
					default:
						return false;
				}
				return true;
			case "money.player.get":
				if ($this->config->exists($data['username'])) return $this->config->get($data['username'])['money'];
				return false;
		}
	}

	public function commandHandler($cmd, $args, $issuer, $alias)
	{
		$cmd = strtolower($cmd);
		if ($issuer !== 'console') {
			return $this->userCommandHandler($cmd, $args, $issuer, $alias);
		}
		switch ($cmd) {
			case "money":
				$subCommand = strtolower($args[0]);
				switch ($subCommand) {
					case "":
					case "help":
						console("[PocketMoney]/money help( or /money )");
						console("[PocketMoney]/money create <account>");
						console("[PocketMoney]/money set <target> <amount>");
						console("[PocketMoney]/money grant <target> <amount>");
						console("[PocketMoney]/money top <amount>");
						console("[PocketMoney]/money stat");
						break;
					case "create":
						$account = $args[1];
						if ($this->config->exists($account)) {
							console("[PocketMoney] The account name already exists");
							break;
						}
						$this->config->set($account, array('money' => self::DEFAULT_MONEY));
						$this->config->save();
						console("[PocketMoney] Opening $account has been completed");
						break;
					case "set":
						$target = $args[1];
						$amount = $args[2];
						if (!$this->config->exists($target)) {
							console("[PocketMoney] $target has not been registered");
							break;
						}
						if (!is_numeric($amount) or $amount < 0) {
							console("[PocketMoney] Invalid amount");
							break;
						}
						$this->config->set($target, array('money' => $amount));
						console("[PocketMoney][set] Done!");
						$this->api->chat->sendTo(false, "[PocketMoney][set] Your money was changed to $amount PM by admin", $target);
						$this->config->save();
						break;
					case "grant":
						$target = $args[1];
						if (!$this->config->exists($target)) {
							console("[PocketMoney] $target has not been registered.");
							break;
						}
						$amount = $args[2];
						$targetMoney = $this->config->get($target)['money'] + $amount;
						if (!is_numeric($amount) or $targetMoney < 0) {
							console("[PocketMoney] Invalid amount.");
							break;
						}
						$this->config->set($target, array('money' => $targetMoney));
						console("[PocketMoney][grant] Done!");
						$this->api->chat->sendTo(false, "[INFO][grant]Your money was changed to $targetMoney PM by admin", $target);
						$this->config->save();
						break;
					case "top":
						$amount = $args[1];
						$temp = array();
						foreach ($this->config->getAll() as $name => $value) {
							$temp[$name] = $value['money'];
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
						console("[PocketMoney] /money $subCommand dose not exist.");
						break;
				}
				break;
		}

	}

	public function userCommandHandler($cmd, $args, $issuer, $alias)
	{
		$output = "";
		$cmd = strtolower($cmd);
		switch ($cmd) {
			case "money":
				$subCommand = $args[0];
				switch ($subCommand) {
					case "":
						$money = $this->config->get($issuer->username)['money'];
						$output .= "[PocketMoney] $money PM";
						break;
					case "help":
						$output .= "[PocketMoney]/money\n";
						$output .= "[PocketMoney]/money help\n";
						$output .= "[PocketMoney]/money pay <target> <amount>\n";
						$output .= "[PocketMoney]/money create <account>\n";
						$output .= "[PocketMoney]/money top <amount>\n";
						$output .= "[PocketMoney]/money stat\n";
						break;
					case "pay":
						$target = $args[1];
						$payer = $issuer->username;
						if ($target === $payer) {
							$output .= "[PocketMoney] Cannot pay yourself!";
							break;
						}
						if (!$this->config->exist($target)) {
							$output .= "[PocketMoney]$target has not been registered.";
							break;
						}
						$targetMoney = $this->config->get($target)['money'];
						$payerMoney = $this->config->get($payer)['money'];
						$amount = $args[2];
						if (!is_numeric($amount) or $amount < 0 or $amount > $payerMoney) {
							$output .= "[PocketMoney] Invalid amount";
							break;
						}
						$targetMoney += $amount;
						$payerMoney -= $amount;
						$this->config->set($target, $targetMoney);
						$this->config->set($payer, $payerMoney);
						$output .= "[PocketMoney][pay] Done!";
						$this->api->chat->sendTo(false, "[PocketMoney] $payer paid $amount PM to you", $target);
						$this->config->save();
						break;
					case "create":
						$account = $args[1];
						if ($this->config->exists($account)) {
							$output .= "[PocketMoney] The account name already exists.";
							break;
						}
						$this->config->set($account, array('money' => self::DEFAULT_MONEY));
						$this->config->save();						
						break;
					case "top":
						$amount = $args[1];
						$temp = array();
						foreach ($this->config->getAll() as $name => $value) {
							$temp[$name] = $value['money'];
						}
						arsort($temp);
						$i = 1;
						$output .= "[PocketMoney] Millionaires\n";
						$output .= "===========================\n";
						foreach ($temp as $name => $money) {
							if ($i > $amount) {
								break;
							}
							$output .= "#$i : $name $money PM\n";
							$i++;
						}
						break;
					case "stat":
						$total = 0;
						$num = 0;
						foreach($this->config->getAll() as $k => $value)
						{
							$total += $value['money'];
							$num++;
						}
						$avr = floor($total / $num);
						$output .= "[PocketMoney] Circ:$total Avr:$avr Accounts:$num";
						break;
					default:
						$output .= "[PocketMoney] /money $subCommand dose not exist.";
						break;
				}
				break;
		}
		return $output;
	}

	public function __destruct()
	{
		$this->config->save();
	}
}
