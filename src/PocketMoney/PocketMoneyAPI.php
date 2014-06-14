<?php

namespace PocketMoney;

use pocketmine\Server;
use pocketmine\utils\Config;

use PocketMoney\

class PocketMoneyAPI
{

	private static $api = null;
	private $config, $system;

	private function __construct()
	{
		$this->config = new Config(Server::getInstance()->getPluginManager()->getPlugin("PocketMoney")->getDataFolder()."config.yml");
		$this->config = new Config(Server::getInstance()->getPluginManager()->getPlugin("PocketMoney")->getDataFolder()."system.yml");
		
	}

	/**
	 * get API instance
	 * @return PocketMoneyAPI
	 */
	public static function getAPI()
	{
		if (is_null(self::$api)) {
			self::$api = new self;
		}

		return self::$api;
	}

	/**
	 * get default money
	 * @return int
	 */
	public function getDefaultMoney()
	{
		return $this->system->get("default_money");
	}

	/**
	 * get money of account
	 * @param  string $account
	 * @return int
	 */
	public function getMoney($account)
	{	
		$this->getType()
		
	}

	public function getType($account)
	{

	}

	public function setMoney($target, $amount)
	{

	}

	public function grantMoney($target, $amoutn)
	{

	}

	public function hideAccount($account)
	{
		$this->config->set($account, array('hide' => true));
		$this->config->save();
	}

	public function unhideAccount($account)
	{
		$this->config->set($account, array('hide' => false));
		$this->config->save();
	}
}