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
        $dataFolder = Server::getInstance()->getPluginManager()->getPlugin("PocketMoney")->getDataFolder();
		$this->config = new Config($dataFolder."config.yml");
		$this->config = new Config($dataFolder."system.yml");
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
     * @return int|SimpleError
     */
    public function getMoney($account)
	{
        if (!$this->config->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        return $this->config->get($account)['money'];
	}

    /**
     * @param string $account
     * @return PlayerType
     */
    public function getType($account)
	{
        if (!$this->config->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        return $this->config->get($account)['type'];
	}

    /**
     * @param string $sender
     * @param string $receiver
     * @param int $amount
     * @return bool|SimpleError
     */
    public function payMoney($sender, $receiver, $amount)
    {
        if (!$this->config->exists($sender)) return new SimpleError(SimpleError::AccountNotExist, "\"$sender\" dose not exist");
        if (!$this->config->exists($sender)) return new SimpleError(SimpleError::AccountNotExist, "\"$receiver\" dose not exist");
        if (($res = $this->grantMoney($sender, -$amount)) !== true) return $res;
        if (($res = $this->grantMoney($receiver, +$amount)) !== true) return $res;
        return true;
    }

    /**
     * @param string $account
     * @param int $amount
     * @return bool
     */
    public function setMoney($account, $amount)
	{
        if (!$this->config->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        if (!is_numeric($amount) or $amount < 0) return new SimpleError(SimpleError::InvalidAmount, "Invalid amount");
        $this->config->set($account, array_merge($this->config->get($account), array("money" => $amount)));
        $this->config->save();
        return true;
	}

    /**
     * @param string $account
     * @param int $amount
     * @return bool|SimpleError
     */
    public function grantMoney($account, $amount)
	{
        if (!$this->config->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        if (!is_numeric($amount) or ($this->getMoney($account) + $amount) < 0) return new SimpleError(SimpleError::InvalidAmount, "Invalid amount");
        $this->config->set($account, array_merge($this->config->get($account), array("money" => $this->config->get($account)['money'] + $amount)));
	    $this->config->save();
        return true;
    }

    /**
     * @param string $account
     * @return bool|SimpleError
     */
    public function hideAccount($account)
	{
        if (!$this->config->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        $this->config->set($account, array_merge($this->config->get($account), array('hide' => true)));
        $this->config->save();
        return true;
	}

    /**
     * @param string $account
     * @return bool|SimpleError
     */
	public function unhideAccount($account)
	{
        if (!$this->config->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        $this->config->set($account, array_merge($this->config->get($account), array('hide' => false)));
        $this->config->save();
        return true;
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
     * @return bool|SimpleError
     */
    public function createAccount($account, PlayerType $type, $hide = false, $money = false)
    {
        if ($this->config->exists($account)) return new SimpleError(SimpleError::AccountAlreadyExist, "\"$account\" already exists");
        $money = ($money === false ? $this->getDefaultMoney() : $money);
        if (!is_numeric($money) or $money < 0) return new SimpleError(SimpleError::InvalidAmount, "Invalid amount");
        $this->config->set($account, array("money" => $money, "type" => $type, "hide" => $hide));
        return true;
    }

    /**
     * @param string $account
     * @return bool|SimpleError
     */
    public function deleteAccount($account)
    {
        if (!$this->config->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        $this->config->remove($account);
        return true;
    }
}