<?php

namespace PocketMoney;

use pocketmine\Server;
use pocketmine\utils\Config;

use PocketMoney\constants\PlayerType;
use PocketMoney\constants\SimpleError;

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
     * @return int
     */
    public function getDefaultMoney()
	{
		return $this->config->get("default_money");
	}

    /**
     * @param string $account
     */
    public function getMoney($account)
	{
        if (!$this->config->exists($account)) return ErrorCode::AccountNotExist;
        return $this->config->get($account)['money'];
	}

    /**
     * @param string $account
     * @return PlayerType
     */
    public function getType($account)
	{
        return $this->config->get($account)['type'];
	}

    public function payMoney($sender, $receiver, $amount)
    {
        $this->grantMoney($sender, -$amount);
        $this->grantMoney($receiver, +$amount);
    }

    /**
     * @param string $account
     * @param int $amount
     */
    public function setMoney($account, $amount)
	{
        $this->config->set($account, array_merge($this->config->get($account), array("money" => $amount)));
        $this->config->save();
	}

    /**
     * @param string $account
     * @param int $amount
     */
    public function grantMoney($account, $amount)
	{
        $this->config->set($account, array_merge($this->config->get($account), array("money" => $this->config->get($account)['money'] + $amount)));
	    $this->config->save();
    }

    /**
     * @param string $account
     */
    public function hideAccount($account)
	{
        $this->config->set($account, array_merge($this->config->get($account), array('hide' => true)));
        $this->config->save();
	}

    /**
     * @param string $account
     */
	public function unhideAccount($account)
	{
        $this->config->set($account, array_merge($this->config->get($account), array('hide' => false)));
        $this->config->save();
	}

    /**
     * @return int
     */
    public function getNumberOfAccount()
    {
        return count($this->config->getAll());
    }

    /**
     * @return int
     */
    public function getTotalMoney()
    {
        $sum = 0;
        foreach ($this->config->getAll() as $account) {
            $sum += $account['money'];
        }
        return $sum;
    }

    /**
     * @param string $account
     * @param PlayerType $type
     * @param bool $hide
     * @param bool|int $money
     */
    public function createAccount($account, PlayerType $type, $hide = false, $money = false)
    {
        $money = ($money === false ? $this->getDefaultMoney() : $money);
        $this->config->set($account, array("money" => $money, "type" => $type, "hide" => $hide));
    }

    /**
     * @param string $account
     */
    public function deleteAccount($account)
    {
        $this->config->remove($account);
    }
}