<?php 

/*
 __PocketMine Plugin__
name=PocketMoney
description=PocketMoney is the foundation of money system for PocketMine
version=2.2.1
author=MinecrafterJPN
class=PocketMoney
apiversion=11
*/

class PocketMoney implements Plugin
{
	public $defaultMoney;
	private $api, $config, $system;

	const TYPE_PLAYER = 0;
	const TYPE_NON_PLAYER = 1;

	public function __construct(ServerAPI $api, $server = false)
	{
		$this->api = $api;
	}

	public function init()
	{
		$this->config = new Config($this->api->plugin->configPath($this) . "config.yml", CONFIG_YAML);
		$this->system = new Config($this->api->plugin->configPath($this) . "system.yml", CONFIG_YAML, array("optimized" => false, "default_money" => 500));
		$this->api->addHandler("player.join", array($this, "eventHandler"));
		$this->api->addHandler("money.handle", array($this, "eventHandler"));
		$this->api->addHandler("money.player.get", array($this, "eventHandler"));
		$this->api->addHandler("money.create.account", array($this, "eventHandler"));

		$this->api->console->register("money", "PocketMoney master command", array($this, "commandHandler"));
		if (!$this->system->get("optimized")) $this->optimizeConfigFile();

		$this->defaultMoney = $this->system->get("default_money");
	}

	public function eventHandler($data, $event)
	{
		switch ($event) {
			case "player.join":
				$target = $data->username;
				if (!$this->config->exists($target)) {
					$this->config->set($target, array('money' => $this->defaultMoney, 'type' => self::TYPE_PLAYER, 'hide' => false));
					$this->api->chat->broadcast("[PocketMoney] $target has been registered");
					$this->config->save();
				}
				break;

			//Should be used by only this plugin
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
						$this->config->set($target, array_merge($this->config->get($target), array('money' => $amount)));
						$this->config->save();
						break;
					case "grant":
						$targetMoney = $this->config->get($target)['money'] + $amount;
						if ($targetMoney < 0) return false;
						$this->config->set($target, array_merge($this->config->get($target), array('money' => $targetMoney)));
						$this->config->save();
						break;
					default:
						return false;
				}
				return true;
			case "money.player.get":
				if (!isset($data['username'])) return false;
				if ($this->config->exists($data['username'])) return $this->config->get($data['username'])['money'];
				return false;
			case "money.create.account":
				if(!isset($data['account']) or !isset($data['hide'])) return false;
				$account = $data['account'];
				$hide = $data['hide'];
				if ($this->config->exists($account)) {
					return false;
				}
				if ($hide !== true and $hide !== false) {
					return false;
				}
				$this->config->set($account, array('money' => $this->defaultMoney, 'type' => self::TYPE_NON_PLAYER, 'hide' => $hide));
				$this->config->save();
				return true;
		}
	}

	public function commandHandler($cmd, $args, $issuer, $alias)
	{
		$cmd = strtolower($cmd);
		if ($issuer !== "console") {
			return $this->userCommandHandler($cmd, $args, $issuer, $alias);
		}
		switch ($cmd) {
			case "money":
				$subCommand = strtolower($args[0]);
				switch ($subCommand) {
					case "":
					case "help":
						console("[PocketMoney] /money help( or /money )");
						console("[PocketMoney] /money view <account>");
						console("[PocketMoney] /money create <account>");
						console("[PocketMoney] /money hide <account>");						
						console("[PocketMoney] /money unhide <account>");
						console("[PocketMoney] /money set <target> <amount>");
						console("[PocketMoney] /money grant <target> <amount>");
						console("[PocketMoney] /money top <amount>");
						console("[PocketMoney] /money stat");
						break;
					case "view":
						$account = $args[1];
						if (empty($account)) {
							console("[PocketMoney] Usage: /money view <account>");
							break;
						}
						if (!$this->config->exists($account)) {
							console("[PocketMoney] The account dose not exist");
							break;
						}
						$money = $this->config->get($account)['money'];
						$type = $this->config->get($account)['type'] === self::TYPE_PLAYER ? "Player" : "Non-player";
						console("[PocketMoney] \"{$account}\" money:$money PM, type:$type");
						break;
					case "create":
						$account = $args[1];
						if (empty($account)) {
							console("[PocketMoney] Usage: /money create <account>");
							break;
						}
						if ($this->config->exists($account)) {
							console("[PocketMoney] The account already exists");
							break;
						}
						$this->config->set($account, array('money' => $this->defaultMoney, 'type' => self::TYPE_NON_PLAYER, 'hide' => false));
						$this->config->save();
						console("[PocketMoney] \"{$account}\" was created");
						break;
					case "hide":
						$acount = $args[1];
						if (empty($account)) {
							console("[PocketMoney] Usage: /money hide <account>");
							break;
						}
						if (!$this->config->exists($account)) {
							console("[PocketMoney] The account dose not exist");
							break;
						}
						if ($this->config->get($account)['hide']) {
							console("[PocketMoney] The account has already been hidden");
							break;
						}
						if ($this->config->get($account)['type'] !== self::TYPE_NON_PLAYER) {
							console("[PocketMoney] You can hide only Non-player account");
							break;
						}
						$this->config->set($account, array_merge($this->config->get($account), array('hide' => true)));
						$this->config->save();
						console("[PocketMoney] \"{$account}\" was hidden");
						break;
					case "unhide":
						$acount = $args[1];
						if (empty($account)) {
							console("[PocketMoney] Usage: /money unhide <account>");
							break;
						}
						if (!$this->config->exists($account)) {
							console("[PocketMoney] The account dose not exist");
							break;
						}
						if (!$this->config->get($account)['hide']) {
							console("[PocketMoney] The account has not been hidden");
							break;
						}
						$this->config->set($account, array_merge($this->config->get($account), array('hide' => false)));
						$this->config->save();
						console("[PocketMoney] \"{$account}\" was unhidden");
						break;
					case "set":
						$target = $args[1];
						$amount = $args[2];
						if (empty($account) or empty($amount)) {
							console("[PocketMoney] Usage: /money set <target> <amount>");
							break;
						}
						if (!$this->config->exists($target)) {
							console("[PocketMoney] The account dose not exist");
							break;
						}
						if (!is_numeric($amount) or $amount < 0) {
							console("[PocketMoney] Invalid amount");
							break;
						}
						$this->config->set($target, array_merge($this->config->get($target), array('money' => $amount)));
						console("[PocketMoney][set] Done!");
						$this->api->chat->sendTo(false, "[PocketMoney][set] Your money was changed to $amount PM by admin", $target);
						$this->config->save();
						break;
					case "grant":
						$target = $args[1];						
						$amount = $args[2];
						if (empty($account) or empty($amount)) {
							console("[PocketMoney] Usage: /money grant <target> <amount>");
							break;
						}
						if (!$this->config->exists($target)) {
							console("[PocketMoney] The account dose not exist");
							break;
						}
						$targetMoney = $this->config->get($target)['money'] + $amount;
						if (!is_numeric($amount) or $targetMoney < 0) {
							console("[PocketMoney] Invalid amount.");
							break;
						}
						$this->config->set($target, array_merge($this->config->get($target), array('money' => $targetMoney)));
						console("[PocketMoney][grant] Done!");
						$this->api->chat->sendTo(false, "[INFO][grant]Your money was changed to $targetMoney PM by admin", $target);
						$this->config->save();
						break;
					case "top":
						$amount = $args[1];
						if (empty($amount)) {
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
				if ($this->config->get($issuer->username)['type'] !== self::TYPE_PLAYER) {
					$output .= "[PocketMoney][Error] Change your username or you can not use PocketMoney commands";
					break;
				}
				$subCommand = strtolower($args[0]);
				switch ($subCommand) {
					case "":
						$money = $this->config->get($issuer->username)['money'];
						$output .= "[PocketMoney] $money PM";
						break;
					case "help":
						$output .= "[PocketMoney] /money\n";
						$output .= "[PocketMoney] /money help\n";
						$output .= "[PocketMoney] /money pay <target> <amount>\n";
						$output .= "[PocketMoney] /money view <account>\n";
						$output .= "[PocketMoney] /money create <account>\n";
						$output .= "[PocketMoney] /money wd <account> <amount>\n";
						$output .= "[PocketMoney] /money hide <account>\n";
						$output .= "[PocketMoney] /money unhide <account>\n";
						$output .= "[PocketMoney] /money top <amount>\n";
						$output .= "[PocketMoney] /money stat\n";
						break;
					case "pay":
						$target = $args[1];
						$amount = $args[2];
						if (empty($target) or empty($amount)) {
							console("[PocketMoney] Usage: /money pay <target> <amount>");
							break;
						}
						$payer = $issuer->username;
						if ($target === $payer) {
							$output .= "[PocketMoney] Cannot pay yourself!";
							break;
						}
						if (!$this->config->exists($target)) {
							$output .= "[PocketMoney] The account dose not exist";
							break;
						}
						$targetMoney = $this->config->get($target)['money'];
						$payerMoney = $this->config->get($payer)['money'];
						if (!is_numeric($amount) or $amount < 0 or $amount > $payerMoney) {
							$output .= "[PocketMoney] Invalid amount";
							break;
						}
						$targetMoney += $amount;
						$payerMoney -= $amount;
						$this->config->set($target, array_merge($this->config->get($target), array('money' => $targetMoney)));
						$this->config->set($payer, array_merge($this->config->get($payer), array('money' => $payerMoney)));
						$output .= "[PocketMoney][pay] Done!";
						$this->api->chat->sendTo(false, "[PocketMoney] $payer -> you: $amount PM", $target);
						$this->config->save();
						break;
					case "view":
						$account = $args[1];
						if (empty($account)) {
							console("[PocketMoney] Usage: /money view <target>");
							break;
						}
						if (!$this->config->exists($account)) {
							$output .= "[PocketMoney] The account dose not exist";
							break;
						}
						if ($this->config->get($account)['type'] !== self::TYPE_NON_PLAYER) {
							$output .= "[PocketMoney] You can view only Non-player account";
							break;
						}
						$money = $this->config->get($account)['money'];
						$output .= "[PocketMoney] \"{$account}\" money: $money PM";
						break;
					case "create":
						$account = $args[1];
						if (empty($account)) {
							console("[PocketMoney] Usage: /money create <account>");
							break;
						}
						if ($this->config->exists($account)) {
							$output .= "[PocketMoney] The account already exists.";
							break;
						}
						$this->config->set($account, array('money' => $this->defaultMoney, 'type' => self::TYPE_NON_PLAYER, 'hide' => false));
						$this->config->save();
						$output .= "[PocketMoney] \"{$account}\" was created";
						break;
					case "wd":
					case "withdraw":
						$account = $args[1];
						$amount = $args[2];
						if (empty($account) or empty($amount)) {
							console("[PocketMoney] Usage: /money wd <account> <amount>");
							break;
						}
						if (!$this->config->exists($account)) {
							$output .= "[PocketMoney] The account dose not exist";
							break;
						}
						if ($this->config->get($account)['type'] !== self::TYPE_NON_PLAYER) {
							$output .= "[PocketMoney] You can withdraw money from only non-player account";
							break;
						}
						$balance = $this->config->get($account);
						if (!is_numeric($amount) or $amount < 0 or $amount > $balance) {
							$output .= "[PocketMoney] Invalid amount";
							break;
						}
						$remittee = $issuer->username;
						$remitteeMoney = $this->config->get($remittee)['money'];
						$this->config->set($account, array_merge($this->config->get($account), array('money' => $balance - $amount)));
						$this->config->set($remittee, array_merge($this->config->get($remittee), array('money' => $remitteeMoney + $amount)));
						$this->config->save();
						$output .= "[PocketMoney] $account -> you: $amount PM";
						break;
					case "hide":
						$acount = $args[1];
						if (empty($account)) {
							console("[PocketMoney] Usage: /money hide <account>");
							break;
						}
						if (!$this->config->exists($account)) {
							$output .= "[PocketMoney] The account dose not exist";
							break;
						}
						if ($this->config->get($account)['hide']) {
							$output .= "[PocketMoney] The account has already been hidden";
							break;
						}
						if ($this->config->get($account)['type'] !== self::TYPE_NON_PLAYER) {
							$output .= "[PocketMoney] You can hide only Non-player account";
							break;
						}
						$this->config->set($account, array_merge($this->config->get($account), array('hide' => true)));
						$this->config->save();
						$output .= "[PocketMoney] \"{$account}\" was hidden";
						break;
					case "unhide":
						$acount = $args[1];
						if (empty($account)) {
							console("[PocketMoney] Usage: /money unhide <account>");
							break;
						}
						if (!$this->config->exists($account)) {
							$output .= "[PocketMoney] The account dose not exist";
							break;
						}
						if (!$this->config->get($account)['hide']) {
							$output .= "[PocketMoney] The account has not been hidden";
							break;
						}
						$this->config->set($account, array_merge($this->config->get($account), array('hide' => false)));
						$this->config->save();
						$output .= "[PocketMoney] \"{$account}\" was unhidden";
						break;
					case "top":
						$amount = $args[1];
						if (empty($account)) {
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

	private function optimizeConfigFile()
	{
		foreach ($this->config->getAll() as $key => $val) {
			if (!array_key_exists("type", $val)) {
				$this->config->set($key, array_merge($this->config->get($key), array('type' => self::TYPE_PLAYER)));
			}
			if (!array_key_exists("hide", $val)) {
				$this->config->set($key, array_merge($this->config->get($key), array('hide' => false)));
			} elseif ($val["hide"] !== true and $val["hide"] !== false) {
				$hideFlag = $val["hide"] === 0 ? false : true;
				$this->config->set($key, array_merge($this->config->get($key), array('hide' => $hideFlag)));
			}
		}
		$this->config->save();
		$this->system->set("optimized", true);
		$this->system->save();
	}

	public static function setMoney($accountName, $amount)
	{
		if ($accountName instanceOf Player) {
			$accountName = $accountName->username;
		}
		return ServerAPI::request()->api->dhandle("money.handle", array(
			"username" => $accountName,
			"method" => "set",
			"amount" => $amount));
	}

	public static function grantMoney($accountName, $amount)
	{
		if ($accountName instanceOf Player) {
			$accountName = $accountName->username;
		}
		return ServerAPI::request()->api->dhandle("money.handle", array(
			"username" => $accountName,
			"method" => "grant",
			"amount" => $amount));
	}

	public static function getMoney($accountName)
	{
		if ($accountName instanceOf Player) {
			$accountName = $accountName->username;
		}
		return ServerAPI::request()->api->dhandle("money.player.get", array(
			"username" => $accountName));		
	}

	public static function createAccount($accountName, $hide = false)
	{
		return ServerAPI::request()->api->dhandle("money.create.account", array(
			"account" => $accountName,
			"hide" => $hide));
	}

	public function __destruct()
	{
		$this->config->save();
		$this->system->save();
	}
}