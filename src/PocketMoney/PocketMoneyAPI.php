<?php

namespace PocketMoney;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

use PocketMoney\constants\PlayerType;
use PocketMoney\Error\SimpleError;

class PocketMoneyAPI
{
	private static $api = null;
	private $users, $system;

	private function __construct()
	{
        $dataFolder = Server::getInstance()->getPluginManager()->getPlugin("PocketMoney")->getDataFolder();
		$this->users = new Config($dataFolder."user.yml");
		$this->system = new Config($dataFolder."system.yml", Config::YAML, array("default_money" => 500));
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
		return $this->system->get("default_money");
	}

    /**
     * @param string $account
     * @return int|SimpleError
     */
    public function getMoney($account)
	{
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, " \"$account\" dose not exist");
        return $this->users->get($account)['money'];
	}

    /**
     * @param string $account
     * @return PlayerType|SimpleError
     */
    public function getType($account)
	{
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, " \"$account\" dose not exist");
        return $this->users->get($account)['type'];
	}

    /**
     * @param string $sender
     * @param string $receiver
     * @param int $amount
     * @return bool|SimpleError
     */
    public function payMoney($sender, $receiver, $amount)
    {
        if (!$this->users->exists($sender)) return new SimpleError(SimpleError::AccountNotExist, " \"$sender\" dose not exist");
        if (!$this->users->exists($sender)) return new SimpleError(SimpleError::AccountNotExist, " \"$receiver\" dose not exist");
        if (($res = $this->grantMoney($sender, -$amount)) !== true) return $res;
        if (($res = $this->grantMoney($receiver, +$amount)) !== true) return $res;
        return true;
    }

    /**
     * @param string $account
     * @param int $amount
     * @return bool|SimplerError
     */
    public function setMoney($account, $amount)
	{
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        if (!is_numeric($amount) or $amount < 0) return new SimpleError(SimpleError::InvalidAmount, "Invalid amount");
        $this->users->set($account, array_merge($this->users->get($account), array("money" => $amount)));
        $this->users->save();
        return true;
	}

    /**
     * @param string $account
     * @param int $amount
     * @return bool|SimpleError
     */
    public function grantMoney($account, $amount)
	{
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        $targetMoney = $this->getMoney($account);
        if (!is_numeric($amount) or ($targetMoney + $amount) < 0) return new SimpleError(SimpleError::InvalidAmount, "Invalid amount");
        $this->users->set($account, array_merge($this->users->get($account), array("money" => $targetMoney + $amount)));
        $this->users->save();
        return true;
    }

    /**
     * @param string$account
     * @param bool $hide
     * @return bool|SimpleError
     */
    public function setAccountHide($account, $hide)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        $this->users->set($account, array_merge($this->users->get($account), array('hide' => $hide)));
        $this->users->save();
        return true;
    }

    /**
     * @param bool $account
     * @return bool|SimpleError
     */
    public function switchHide($account)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        $hide = $this->users->get($account)['hide'];
        $this->users->set($account, array_merge($this->users->get($account), array('hide' => !$hide)));
        $this->users->save();
        return true;
    }

    /**
     * @param string $account
     * @return bool|SimpleError
     */
    public function hideAccount($account)
	{
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        $this->users->set($account, array_merge($this->users->get($account), array('hide' => true)));
        $this->users->save();
        return true;
	}

    /**
     * @param string $account
     * @return bool|SimpleError
     */
	public function unhideAccount($account)
	{
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        $this->users->set($account, array_merge($this->users->get($account), array('hide' => false)));
        $this->users->save();
        return true;
	}

    /**
     * @return int
     */
    public function getNumberOfAccount()
    {
        return count($this->users->getAll());
    }

    /**
     * @return int
     */
    public function getTotalMoney()
    {
        $sum = 0;
        foreach ($this->users->getAll() as $account) {
            $sum += $account['money'];
        }
        return $sum;
    }

    /**
     * @param string $account
     * @param int $type
     * @param bool $hide
     * @param bool|int $money
     * @return bool|SimpleError
     */
    public function createAccount($account, $type, $hide = false, $money = false)
    {
        if ($this->users->exists($account)) return new SimpleError(SimpleError::AccountAlreadyExist, "\"$account\" already exists");
        $money = ($money === false ? $this->getDefaultMoney() : $money);
        if (!is_numeric($money) or $money < 0) return new SimpleError(SimpleError::InvalidAmount, "Invalid amount");
        if (!is_numeric($type)) {
            if (strtolower($type) === "player") {
                $type = PlayerType::Player;
            } elseif(strtolower($type) === "nonplayer") {
                $type = PlayerType::NonPlayer;
            } else {
                return new SimpleError(SimpleError::InvalidAmount, "Invalid amount");
            }
        }
        $this->users->set($account, array("money" => $money, "type" => $type, "hide" => $hide));
        return true;
    }

    /**
     * @param string $account
     * @return bool|SimpleError
     */
    public function deleteAccount($account)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" dose not exist");
        $this->users->remove($account);
        return true;
    }
}