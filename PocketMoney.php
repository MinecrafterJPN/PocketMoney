<?php 

/*
 __PocketMine Plugin__
name=PocketMoney
description=PocketMoney introduces economy into your PocketMine world.
version=1.7.2
author=MinecrafterJPN
class=PocketMoney
apiversion=9
*/

define("DEFAULT_MONEY", 500);

class PocketMoney implements Plugin
{
	private $api, $path;

	public function __construct(ServerAPI $api, $server = false)
	{
		$this->api = $api;
	}

	public function init()
	{
		$this->api->addHandler("player.join", array($this, "eventHandler"));
		$this->api->addHandler("money.handle", array($this, "eventHandler"));
		$this->api->addHandler("money.player.get", array($this, "eventHandler"));
		$this->api->console->register("money", "PocketMoney command", array($this, "commandHandler"));

		$this->path = $this->api->plugin->createConfig($this, array());
	}

	public function eventHandler($data, $event)
	{
		$cfg = $this->api->plugin->readYAML($this->path . "config.yml");
		switch($event)
		{
			case "player.join":
				$target = $data->username;
				if(!array_key_exists($target, $cfg))
				{
					$this->api->plugin->createConfig($this,array(
							$target => array(
									'money' => DEFAULT_MONEY
							)
					));
					$this->api->chat->broadcast("[PocketMoney]$target has been registered.");
				}
				break;
			case "money.handle":
				if(!isset($data['username']) or !isset($data['method']) or !isset($data['amount'])) return false;
				$target = $data['username'];
				$method = $data['method'];
				$amount = (int)$data['amount'];
				if($this->api->player->get($target) === false or !array_key_exists($target, $cfg) or !is_numeric($amount))
				{
					return false;
				}
				switch($method)
				{
					case "set":
						if($amount < 0)
						{
							return false;
						}
						$result = array(
								$target => array(
										'money' => $amount
								)
						);
						$this->overwriteConfig($result);
						break;
					case "grant":
						$targetMoney = $cfg[$target]['money'] + $amount;
						if($targetMoney < 0) return false;
						$result = array(
								$target => array(
										'money' => $targetMoney
								)
						);
						$this->overwriteConfig($result);
						break;
					default:
						return false;
				}
				return true;
			case "money.player.get":
				if(array_key_exists($data['username'], $cfg))
				{
					return $cfg[$data['username']]['money'];
				}
				return false;
		}
	}
	public function userCommandHandler($cmd, $args, $issuer, $alias)
	{
		$output = "";
		switch($cmd){
			case "money":
				$subCommand = $args[0];
				$cfg = $this->api->plugin->readYAML($this->path . "config.yml");
				switch($subCommand){
					case "":
						if(!array_key_exists($issuer->username, $cfg))
						{
							$output .= "[PocketMoney]You have not been registered.";
							break;
						}
						$money = $cfg[$issuer->username]['money'];
						$output .= "[PocketMoney]$money PM";
						break;
					case "pay":
						$target = $args[1];
						$payer = $issuer->username;
						if($target === $payer)
						{
							$output .= "[PocketMoney]You cannot pay yourself.";
							break;
						}
						if(!$this->api->player->get($target))
						{
							$output .= "[PocketMoney]$target is not in the server now.";
							break;
						}
						$targetMoney = $cfg[$target]['money'];
						$payerMoney = $cfg[$payer]['money'];
						$amount = $args[2];
						if(!is_numeric($amount) or $amount < 0 or $amount > $payerMoney)
						{
							$output .= "[PocketMoney]Invalid amount.";
							break;
						}
						$targetMoney += $amount;
						$payerMoney -= $amount;
						$result = array(
								$payer => array(
										'money' => $payerMoney
								),
								$target => array(
										'money' => $targetMoney
								)
						);
						$this->overwriteConfig($result);
						$output .= "[PocketMoney]Completed pay process.";
						$this->api->chat->sendTo(false, "[PocketMoney]$amount PM paid from $payer", $target);
						break;
					case "top":
						$amount = $args[1];
						$temp = array();
						foreach($cfg as $name => $elements)
						{
							$temp[$name] = $elements['money'];
						}
						arsort($temp);
						$i = 1;
						$output .= "[PocketMoney]List of Millionaires\n";
						foreach($temp as $name => $money)
						{
							if($i > $amount){
								break;
							}
							$output .= "#$i : $name $money PM\n";
							$i++;
						}
						break;
					case "stat":
						$total = 0;
						$num = 0;
						foreach($cfg as $name => $elements)
						{
							$total += $elements['money'];
							$num++;
						}
						$avr = floor($total / $num);
						$output .= "[PocketMoney]Circ:$total Avr:$avr Accounts:$num";
						break;
					default:
						$output .= "[PocketMoney]Such sub command dose not exist.";
						break;
				}
				break;
		}
		return $output;
	}

	public function commandHandler($cmd, $args, $issuer, $alias)
	{
		$cmd = strtolower($cmd);
		if($issuer !== "console")
		{
			return $this->userCommandHandler($cmd, $args, $issuer, $alias);
		}
		switch($cmd)
		{
			case "money":
				$cfg = $this->api->plugin->readYAML($this->path . "config.yml");
				$subCommand = $args[0];
				switch($subCommand)
				{
					case "":
					case "help":
						console("[PocketMoney]/money help( or /money )");
						console("[PocketMoney]/money set <target> <amount>");
						console("[PocketMoney]/money grant <target> <amount>");
						break;
					case "set":
						$target = $args[1];
						$amount = $args[2];
						if(!$this->api->player->get($target))
						{
							console("[PocketMoney]$target is not in the server now.");
							break;
						}
						/*if(!array_key_exists($target, $cfg))
						 {
						console("[PocketMoney]$target has not been registered.");
						break;
						} */
						if($amount < 0 or !is_numeric($amount))
						{
							console("[PocketMoney]Invalid amount.");
							break;
						}
						$result = array(
								$target => array(
										'money' => $amount
								)
						);
						$this->overwriteConfig($result);
						console("[PocketMoney]Completed set process.");
						$this->api->chat->sendTo(false, "[PocketMoney][set]Your money has been changed.\n$target:$amount PM", $target);
						break;
					case "grant":
						$target = $args[1];
						if(!$this->api->player->get($target))
						{
							console("[PocketMoney]$target is not in the server now.");
							break;
						}
						if(!array_key_exists($target, $cfg))
						{
							console("[PocketMoney]$target has not been registered.");
							break;
						}
						$amount = $args[2];
						$targetMoney = $cfg[$target]['money'] + $amount;
						if(!is_numeric($amount) or $targetMoney < 0)
						{
							console("[PocketMoney]Invalid amount.");
							break;
						}
						$result = array(
								$target => array(
										'money' => $targetMoney
								)
						);
						$this->overwriteConfig($result);
						console("[PocketMoney]Completed grant process.");
						$this->api->chat->sendTo(false, "[INFO][grant]Your money has been changed.\n$target:$targetMoney PM", $target);
						break;
					case "top":
						$amount = $args[1];
						$temp = array();
						foreach($cfg as $name => $elements)
						{
							$temp[$name] = $elements['money'];
						}
						arsort($temp);
						$i = 1;
						console("[PocketMoney]List of Millionaires");
						foreach($temp as $name => $money)
						{
							if($i > $amount)
							{
								break;
							}
							console("#$i : $name $money PM");
							$i++;
						}
						break;
					case "stat":
						$total = 0;
						$num = 0;
						foreach($cfg as $name => $elements)
						{
							$total += $elements['money'];
							$num++;
						}
						$avr = floor($total / $num);
						console("[PocketMoney]Circulation:$total Average:$avr Accounts:$num");
						break;
					default:
						console("[PocketMoney]Such command dose not exist.");
						break;
				}
				break;
		}

	}

	private function overwriteConfig($dat)
	{
		$cfg = array();
		$cfg = $this->api->plugin->readYAML($this->path . "config.yml");
		$result = array_merge($cfg, $dat);
		$this->api->plugin->writeYAML($this->path."config.yml", $result);
	}

	public function __destruct()
	{
	}
}
