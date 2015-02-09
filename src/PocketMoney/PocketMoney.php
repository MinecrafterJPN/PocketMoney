<?php

namespace PocketMoney;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;

use PocketMoney\Error\SimpleError;
use PocketMoney\constants\PlayerType;
use PocketMoney\Events\MoneyUpdateEvent;
use PocketMoney\Events\TransactionEvent;

class PocketMoney extends PluginBase
{
    /* @var Config */
    private $users;
    /* @var Config */
    private $system;

    // <- API

    /**
     * @api
     *
     * @return int
     */
    public function getDefaultMoney()
    {
        return $this->system->get("default_money");
    }

    /**
     * @api
     *
     * @param string $account
     * @return int|SimpleError
     */
    public function getMoney($account)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, " \"$account\" does not exist");
        return $this->users->get($account)['money'];
    }

    /**
     * @api
     *
     * @param string $account
     * @return PlayerType|SimpleError
     */
    public function getType($account)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, " \"$account\" does not exist");
        return $this->users->get($account)['type'];
    }

    /**
     * @api
     *
     * @param string $account
     * @return bool|SimpleError
     */
    public function getHide($account)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, " \"$account\" does not exist");
        return $this->users->get($account)['hide'];
    }

    /**
     * @api
     *
     * @param string $sender
     * @param string $receiver
     * @param int $amount
     * @return bool|SimpleError
     */
    public function payMoney($sender, $receiver, $amount)
    {
        if (!is_numeric($amount) or $amount < 0) return new SimpleError(SimpleError::InvalidAmount, "Invalid amount");
        if (!$this->users->exists($sender)) return new SimpleError(SimpleError::AccountNotExist, " \"$sender\" does not exist");
        if (!$this->users->exists($sender)) return new SimpleError(SimpleError::AccountNotExist, " \"$receiver\" does not exist");
        if (($res = $this->grantMoney($sender, -$amount)) !== true) return $res;
        if (($res = $this->grantMoney($receiver, +$amount)) !== true) return $res;
        $this->getServer()->getPluginManager()->callEvent(
            new MoneyUpdateEvent(
                $this,
                $this->getServer()->getPlayer($sender),
                $this->getMoney($sender),
                MoneyUpdateEvent::CAUSE_PAY));
        $this->getServer()->getPluginManager()->callEvent(
            new MoneyUpdateEvent(
                $this,
                $this->getServer()->getPlayer($receiver),
                $this->getMoney($receiver),
                MoneyUpdateEvent::CAUSE_PAY));

        $this->getServer()->getPluginManager()->callEvent(
            new TransactionEvent(
                $this,
                $this->getServer()->getPlayer($sender),
                $this->getServer()->getPlayer($receiver),
                $amount,
                TransactionEvent::TRANSACTION_PAY));

        return true;
    }

    /**
     * @api
     *
     * @param string $account
     * @param int $amount
     * @return bool|SimpleError
     */
    public function setMoney($account, $amount)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" does not exist");
        if (!is_numeric($amount) or $amount < 0) return new SimpleError(SimpleError::InvalidAmount, "Invalid amount");
        $this->users->set($account, array_merge($this->users->get($account), array("money" => $amount)));
        $this->users->save();
        $this->getServer()->getPluginManager()->callEvent(
            new MoneyUpdateEvent(
                $this,
                $this->getServer()->getPlayer($account),
                $amount,
                MoneyUpdateEvent::CAUSE_SET));
        return true;
    }

    /**
     * @api
     *
     * @param string $account
     * @param int $amount
     * @return bool|SimpleError
     */
    public function grantMoney($account, $amount)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" does not exist");
        $targetMoney = $this->getMoney($account);
        if (!is_numeric($amount) or ($targetMoney + $amount) < 0) return new SimpleError(SimpleError::InvalidAmount, "Invalid amount");
        $this->users->set($account, array_merge($this->users->get($account), array("money" => $targetMoney + $amount)));
        $this->users->save();
        $this->getServer()->getPluginManager()->callEvent(
            new MoneyUpdateEvent(
                $this,
                $this->getServer()->getPlayer($account),
                $this->getMoney($account),
                MoneyUpdateEvent::CAUSE_GRANT));
        return true;
    }

    /**
     * @api
     *
     * @param string$account
     * @param bool $hide
     * @return bool|SimpleError
     */
    public function setAccountHideMode($account, $hide)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" does not exist");
        $this->users->set($account, array_merge($this->users->get($account), array('hide' => $hide)));
        $this->users->save();
        return true;
    }

    /**
     * @api
     *
     * @param bool $account
     * @return bool|SimpleError
     */
    public function switchHideMode($account)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" does not exist");
        $hide = $this->users->get($account)['hide'];
        $this->users->set($account, array_merge($this->users->get($account), array('hide' => !$hide)));
        $this->users->save();
        return true;
    }

    /**
     * @api
     *
     * @param string $account
     * @return bool|SimpleError
     */
    public function hideAccount($account)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" does not exist");
        if ($this->getType($account) !== PlayerType::NonPlayer) return new SimpleError(SimpleError::Other, "You can hide only Non-player account");
        $this->users->set($account, array_merge($this->users->get($account), array('hide' => true)));
        $this->users->save();
        return true;
    }

    /**
     * @api
     *
     * @param string $account
     * @return bool|SimpleError
     */
    public function unhideAccount($account)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" does not exist");
        $this->users->set($account, array_merge($this->users->get($account), array('hide' => false)));
        $this->users->save();
        return true;
    }

    /**
     * @api
     *
     * @return int
     */
    public function getNumberOfAccount()
    {
        return count($this->users->getAll());
    }

    /**
     * @api
     *
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
     * @api
     *
     * @param string $account
     * @param int|string $type
     * @param bool $hide
     * @param bool|int $money
     * @return bool|SimpleError
     */
    public function createAccount($account, $type = PlayerType::NonPlayer, $hide = false, $money = false)
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
        $this->users->save();
        return true;
    }

    /**
     * @api
     *
     * @param string $account
     * @return bool|SimpleError
     */
    public function deleteAccount($account)
    {
        if (!$this->users->exists($account)) return new SimpleError(SimpleError::AccountNotExist, "\"$account\" does not exist");
        $this->users->remove($account);
        $this->users->save();
        return true;
    }

    /**
     * @api
     *
     * @param int $amount
     * @param bool $includeHideAccount
     * @return array
     */
    public function getRanking($amount, $includeHideAccount = false)
    {
        $result = array();
        $temp = array();
        foreach ($this->users->getAll() as $name => $value) {
            if ($includeHideAccount) {
                $temp[$name] = $value['money'];
            } elseif (!$value['hide']) {
                $temp[$name] = $value['money'];
            }
        }
        arsort($temp);
        $key = array_keys($temp);
        $val = array_values($temp);
        for ($i = 0; $i < $amount; $i++) {
            $tKey = array_shift($key);
            if (is_null($tKey)) break;
            $tVal = array_shift($val);
            if (is_null($tVal)) break;
            $result[$tKey] = $tVal;
        }
        return $result;
    }

    // API ->



	public function onLoad()
	{
	}

	public function onEnable()
	{
        if (!file_exists($this->getDataFolder())) @mkdir($this->getDataFolder(), 0755, true);
        $this->users = new Config($this->getDataFolder()."user.yml", Config::YAML);
        $this->system = new Config($this->getDataFolder()."system.yml", Config::YAML, array("default_money" => 500));
        $this->users->save();
        $this->system->save();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }

	public function onDisable()
	{
        $this->users->save();
        $this->system->save();
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

                        $money = $this->getMoney($account);
                        $type = $this->getType($account);
                        $hide = $this->getHide($account);

						if ($money instanceof SimpleError) {
							$sender->sendMessage($money->getDescription());
							break;
						}
                        if ($type instanceof SimpleError) {
                            $sender->sendMessage($type->getDescription());
                            break;
                        }
                        if ($hide instanceof SimpleError) {
                            $sender->sendMessage($hide->getDescription());
                            break;
                        }
						$type = ($type === PlayerType::Player) ? "Player" : "Non-player";
                        $hide = ($hide === false) ? "false" : "true";
						$sender->sendMessage("\"$account\" money:$money PM type:$type hide:$hide");
						break;

					case "create":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("Usage: /money create <account>");
							break;
						}

                        if (($err = $this->createAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("Successfully created \"$account\"");
						break;

					case "hide":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("Usage: /money hide <account>");
							break;
						}

                        if (($err = $this->hideAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("Successfully hid \"$account\"");
						break;

                    case "unhide":
                    case "expose":
						$account = array_shift($args);
						if (is_null($account)) {
							$sender->sendMessage("Usage: /money unhide <account>");
							break;
						}
                        if (($err = $this->unhideAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("Successfully unhid \"$account\"");
						break;

					case "set":
						$target = array_shift($args);						
						$amount = array_shift($args);
						if (is_null($target) or is_null($amount)) {
							$sender->sendMessage("Usage: /money set <target> <amount>");
							break;
						}
                        if (($err = $this->setMoney($target, $amount)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
						$sender->sendMessage("[set] Done!");
                        if (($player = $this->getServer()->getPlayer($target)) instanceof Player) {
                            $player->sendMessage("Your money was changed to $amount PM by admin");
                        }
						break;

					case "grant":
						$target = array_shift($args);						
						$amount = array_shift($args);
						if (is_null($target) or is_null($amount)) {
							$sender->sendMessage("Usage: /money grant <target> <amount>");
							break;
						}
                        if (($err = $this->grantMoney($target, $amount)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("[grant] Done!");
                        if (($player = $this->getServer()->getPlayer($target)) instanceof Player) {
                            $player->sendMessage("You were granted $amount PM by admin");
                        }
                        break;
						
					case "top":
						$amount = array_shift($args);
                        if (is_null($amount)) {
                            $sender->sendMessage("Usage: /money top <amount>");
							break;
						}
                        $sender->sendMessage("Millionaires");
                        $sender->sendMessage("-* ======= *-");
                        $rank = 1;
						foreach ($this->getRanking($amount) as $name => $money) {
                            $sender->sendMessage("#$rank : $name $money PM");
							$rank++;
						}
                        $sender->sendMessage("-* ======= *-");
						break;
					case "stat":
						$totalMoney = $this->getTotalMoney();
                        $accountNum = $this->getNumberOfAccount();
						$avr = floor($totalMoney / $accountNum);
                        $sender->sendMessage("Circulation:$totalMoney Average:$avr Accounts:$accountNum");
						break;

					default:
                        $sender->sendMessage("\"/money $subCommand\" does not exist");
						break;
				}
				return true;

            default:
                return false;
		}
	}

	private function onCommandByUser(CommandSender $sender, Command $command, $label, array $args)
	{
        switch ($command->getName()) {
            case "money":
                $subCommand = strtolower(array_shift($args));
                switch ($subCommand) {
                    case "":
                        $money = $this->getMoney($sender->getName());
                        $sender->sendMessage("$money PM");
                        break;
                    case "help":
                        $sender->sendMessage("/money help");
                        $sender->sendMessage("/money view <account>");
                        $sender->sendMessage("/money pay <target>");
                        $sender->sendMessage("/money create <account>");
                        $sender->sendMessage("/money hide <account>");
                        $sender->sendMessage("/money unhide <account>");
                        $sender->sendMessage("/money wd <target> <amount>");
                        $sender->sendMessage("/money top <amount>");
                        $sender->sendMessage("/money stat");
                        break;

                    case "view":
                        $account = array_shift($args);
                        if (is_null($account)) {
                            $sender->sendMessage("Usage: /money view <account>");
                            break;
                        }

                        $money = $this->getMoney($account);
                        $type = $this->getType($account);
                        $hide = $this->getHide($account);
                        if ($money instanceof SimpleError) {
                            $sender->sendMessage($money->getDescription());
                            break;
                        }
                        if ($type instanceof SimpleError) {
                            $sender->sendMessage($type->getDescription());
                            break;
                        }
                        if ($hide instanceof SimpleError) {
                            $sender->sendMessage($hide->getDescription());
                            break;
                        }
                        $type = ($type === PlayerType::Player) ? "Player" : "Non-player";
                        $hide = ($hide === false) ? "false" : "true";
                        $sender->sendMessage("\"$account\" money:$money PM type:$type hide:$hide");
                        break;

                    case "pay":
                        $target = array_shift($args);
                        $amount = array_shift($args);
                        if (is_null($target) or is_null($amount)) {
                            $sender->sendMessage("Usage: /money pay <target> <amount>");
                            break;
                        }
                        if (($err = $this->payMoney($sender->getName(), $target, $amount)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("you -> $target: $amount PM");
                        if (($targetPlayer = $this->getServer()->getPlayer($target)) instanceof Player) {
                            $targetPlayer->sendMessage($sender->getName()." -> you: $amount PM");
                        }
                        break;

                    case "withdraw":
                    case "wd":
                        $target = array_shift($args);
                        $amount = array_shift($args);
                        if (is_null($target) or is_null($amount)) {
                            $sender->sendMessage("Usage: /money wd <target> <amount>");
                            break;
                        }
                        if (($type = $this->getType($target)) instanceof SimpleError) {
                            $sender->sendMessage($type->getDescription());
                            break;
                        }
                        if ($type !== PlayerType::NonPlayer) {
                            $sender->sendMessage("You can withdraw money from only non-player account");
                            break;
                        }
                        if (($err = $this->payMoney($target, $sender->getName(), $amount)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("$target -> you: $amount PM");
                        break;

                    case "create":
                        $account = array_shift($args);
                        if (is_null($account)) {
                            $sender->sendMessage("Usage: /money create <account>");
                            break;
                        }
                        if (($err = $this->createAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("Successfully created \"$account\"");
                        break;

                    case "hide":
                        $account = array_shift($args);
                        if (is_null($account)) {
                            $sender->sendMessage('Usage: /money hide <account>');
                            break;
                        }
                        if (($err = $this->hideAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("Successfully hid \"$account\"");
                        break;

                    case "unhide":
                    case "expose":
                        $account = array_shift($args);
                        if (is_null($account)) {
                            $sender->sendMessage("Usage: /money unhide <account>");
                            break;
                        }
                        if (($err = $this->unhideAccount($account)) instanceof SimpleError) {
                            $sender->sendMessage($err->getDescription());
                            break;
                        }
                        $sender->sendMessage("Successfully unhid \"$account\"");
                        break;

                    case "top":
                        $amount = array_shift($args);
                        if (is_null($amount)) {
                            $sender->sendMessage("Usage: /money top <amount>");
                            break;
                        }
                        $sender->sendMessage("Millionaires");
                        $sender->sendMessage("-* ======= *-");
                        $rank = 1;
                        foreach ($this->getRanking($amount) as $name => $money) {
                            $sender->sendMessage("#$rank : $name $money PM");
                            $rank++;
                        }
                        $sender->sendMessage("-* ======= *-");
                        break;
                    case "stat":
                        $totalMoney = $this->getTotalMoney();
                        $accountNum = $this->getNumberOfAccount();
                        $avr = floor($totalMoney / $accountNum);
                        $sender->sendMessage("Circulation:$totalMoney Average:$avr Accounts:$accountNum");
                        break;

                    default:
                        $sender->sendMessage("\"/money $subCommand\" does not exist");
                        break;
                }
                return true;

            default:
                return false;
        }
	}
}
