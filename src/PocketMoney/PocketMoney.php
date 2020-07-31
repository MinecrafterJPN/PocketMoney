<?php

namespace PocketMoney;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;

use PocketMoney\constants\PlayerType;
use PocketMoney\event\MoneyUpdateEvent;
use PocketMoney\event\TransactionEvent;

class PocketMoney extends PluginBase implements Listener
{
    /* @var Config */
    private $users;
    /* @var Config */
    private $system;

    private $messages;



    // <- API

    /**
     * @api
     *
     * return if $account is registered
     *
     * @param string $account
     * @return bool
     */
    public function isRegistered($account)
    {
        return $this->users->exists($account);
    }

    /**
     * @api
     *
     * return default money
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
     * return $account's money
     *
     * @param string $account
     * @return int|false
     */
    public function getMoney($account)
    {
        if (!$this->isRegistered($account)) return false;
        return $this->users->get($account)['money'];
    }

    /**
     * @api
     *
     * return $account's account type
     *
     * @param string $account
     * @return int|false
     */
    public function getType($account)
    {
        if (!$this->isRegistered($account)) return false;
        return $this->users->get($account)['type'];
    }

    /**
     * @api
     *
     * return if $account is hid
     *
     * @param string $account
     * @return bool
     */
    public function getHide($account)
    {
        if (!$this->isRegistered($account)) return false;
        return $this->users->get($account)['hide'];
    }

    /**
     * @api
     *
     * $sender pays $receiver $amount PM
     * return if the transaction is succeeded
     *
     * @param string $sender
     * @param string $receiver
     * @param int $amount
     * @return bool
     */
    public function payMoney($sender, $receiver, $amount)
    {
        if (!is_numeric($amount) or $amount < 0) return false;
        if (!$this->isRegistered($sender)) return false;
        if (!$this->isRegistered($sender)) return false;
        if (!$this->grantMoney($sender, -$amount, false)) return false;
        if (!$this->grantMoney($receiver, $amount, false)) return false;
        $this->getServer()->getPluginManager()->callEvent(
            new MoneyUpdateEvent(
                $this,
                $sender,
                $this->getMoney($sender),
                MoneyUpdateEvent::CAUSE_PAY));
        $this->getServer()->getPluginManager()->callEvent(
            new MoneyUpdateEvent(
                $this,
                $receiver,
                $this->getMoney($receiver),
                MoneyUpdateEvent::CAUSE_PAY));

        $this->getServer()->getPluginManager()->callEvent(
            new TransactionEvent(
                $this,
                $sender,
                $receiver,
                $amount,
                TransactionEvent::TRANSACTION_PAY));

        return true;
    }

    /**
     * @api
     *
     * set $amount to $account's money
     * return if the transaction is succeeded
     *
     * @param string $account
     * @param int $amount
     * @return bool
     */
    public function setMoney($account, $amount)
    {
        if (!$this->isRegistered($account)) return false;
        if (!is_numeric($amount) or $amount < 0) return false;
        $this->users->set($account, array_merge($this->users->get($account), array("money" => $amount)));
        $this->users->save();
        $this->getServer()->getPluginManager()->callEvent(
            new MoneyUpdateEvent(
                $this,
                $account,
                $amount,
                MoneyUpdateEvent::CAUSE_SET));
        return true;
    }

    /**
     * @api
     *
     * grant $amount to $account
     * return if the transaction is succeeded
     *
     * @param string $account
     * @param int $amount
     * @param bool $callEvent
     * @return bool
     */
    public function grantMoney($account, $amount, $callEvent = true)
    {
        if (!$this->isRegistered($account)) return false;
        $targetMoney = $this->getMoney($account);
        if (!is_numeric($amount) or ($targetMoney + $amount) < 0) return false;
        $this->users->set($account, array_merge($this->users->get($account), array("money" => $targetMoney + $amount)));
        $this->users->save();
        if ($callEvent) {
            $this->getServer()->getPluginManager()->callEvent(
                new MoneyUpdateEvent(
                    $this,
                    $account,
                    $this->getMoney($account),
                    MoneyUpdateEvent::CAUSE_GRANT));
        }

        return true;
    }

    /**
     * @api
     *
     * set $account's hide mode
     * return if the transaction is succeeded
     *
     * @param string $account
     * @param bool $hide
     * @return bool
     */
    public function setAccountHideMode($account, $hide)
    {
        if (!$this->isRegistered($account)) return false;
        $this->users->set($account, array_merge($this->users->get($account), array('hide' => $hide)));
        $this->users->save();
        return true;
    }

    /**
     * @api
     *
     * switch $account's hide mode
     * return if the transaction is succeeded
     *
     * @param bool $account
     * @return bool
     */
    public function switchHideMode($account)
    {
        if (!$this->isRegistered($account)) return false;
        $hide = $this->users->get($account)['hide'];
        $this->users->set($account, array_merge($this->users->get($account), array('hide' => !$hide)));
        $this->users->save();
        return true;
    }

    /**
     * @api
     *
     * hide $account
     * return if the transaction is succeeded
     *
     * @param string $account
     * @return bool
     */
    public function hideAccount($account)
    {
        if (!$this->isRegistered($account)) return false;
        if ($this->getType($account) !== PlayerType::NonPlayer) return false;
        $this->users->set($account, array_merge($this->users->get($account), array('hide' => true)));
        $this->users->save();
        return true;
    }

    /**
     * @api
     *
     * unhide $account
     * return if the transaction is succeeded
     *
     * @param string $account
     * @return bool
     */
    public function unhideAccount($account)
    {
        if (!$this->isRegistered($account)) return false;
        $this->users->set($account, array_merge($this->users->get($account), array('hide' => false)));
        $this->users->save();
        return true;
    }

    /**
     * @api
     *
     * return number of account
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
     * return total money
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
     * create $account
     * return if the transaction is succeeded
     *
     * @param string $account
     * @param int|string $type
     * @param bool $hide
     * @param bool|int $money
     * @return bool
     */
    public function createAccount($account, $type = PlayerType::NonPlayer, $hide = false, $money = false)
    {
        if ($this->isRegistered($account)) return false;
        //return new SimpleError(SimpleError::AccountAlreadyExist, "\"$account\" already exists");
        $money = ($money === false ? 0 : $money);
        if (!is_numeric($money) or $money < 0) return false;
        //return new SimpleError(SimpleError::InvalidAmount, "Invalid amount");
        if (!is_numeric($type)) {
            if (strtolower($type) === "player") {
                $type = PlayerType::Player;
            } elseif (strtolower($type) === "nonplayer") {
                $type = PlayerType::NonPlayer;
            } else {
                return false;
                //return new SimpleError(SimpleError::InvalidAmount, "Invalid amount");
            }
        }
        $this->users->set($account, array("money" => $money, "type" => $type, "hide" => $hide));
        $this->users->save();
        return true;
    }

    /**
     * @api
     *
     * delete $account
     * return if the transaction is succeeded
     *
     * @param string $account
     * @return bool
     */
    public function deleteAccount($account)
    {
        if (!$this->isRegistered($account)) return false;
        //return new SimpleError(SimpleError::AccountNotExist, "\"$account\" does not exist");
        $this->users->remove($account);
        $this->users->save();
        return true;
    }

    /**
     * @api
     *
     * return ranking
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
        $this->users = new Config($this->getDataFolder() . "user.yml", Config::YAML);
        $this->system = new Config($this->getDataFolder() . "system.yml", Config::YAML, array("default_money" => 500, "currency" => "PM"));
        $this->users->save();
        $this->system->save();

        $this->saveResource("messages.yml", false);
        $this->messages = $this->parseMessages((new Config($this->getDataFolder() . "messages.yml"))->getAll());

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable()
    {
        $this->users->save();
        $this->system->save();
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
        if ($sender instanceof Player) return $this->onCommandByUser($sender, $command, $label, $args);
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
                            $sender->sendMessage($this->getMessage("view.usage"));
                            break;
                        }

                        $money = $this->getMoney($account);
                        $type = $this->getType($account);
                        $hide = $this->getHide($account);
                        if ($money === false || $type === false || $hide === true) {
                            $sender->sendMessage($this->getMessage("view.fail"));
                            break;
                        }
                        $type = ($type === PlayerType::Player) ? $this->getMessage("view.type.player") : $this->getMessage("view.type.non-player");
                        $hide = ($hide === false) ? $this->getMessage("view.hide.false") : $this->getMessage("view.hide.true");
                        $result = $this->getMessage("view.result");
                        $result = str_replace("{{account}}", $account, $result);
                        $result = str_replace("{{money}}", $this->getFormattedMoney($money), $result);
                        $result = str_replace("{{type}}", $type, $result);
                        $result = str_replace("{{hide}}", $hide, $result);

                        $sender->sendMessage($result);
                        break;

                    case "create":
                        $account = array_shift($args);
                        if (is_null($account)) {
                            $sender->sendMessage($this->getMessage("create.usage"));
                            break;
                        }
                        if (!$this->createAccount($account)) {
                            $sender->sendMessage($this->getMessage("create.fail"));
                            break;
                        }
                        $sender->sendMessage(str_replace("{{account}}", $account, $this->getMessage("create.success")));
                        break;

                    case "hide":
                        $account = array_shift($args);
                        if (is_null($account)) {
                            $sender->sendMessage($this->getMessage("hide.usage"));
                            break;
                        }
                        if (!$this->hideAccount($account)) {
                            $sender->sendMessage($this->getMessage("hide.fail"));
                            break;
                        }
                        $sender->sendMessage(str_replace("{{account}}", $account, $this->getMessage("hide.success")));
                        break;

                    case "unhide":
                    case "expose":
                        $account = array_shift($args);
                        if (is_null($account)) {
                            $sender->sendMessage($this->getMessage("unhide.usage"));
                            break;
                        }
                        if (!$this->unhideAccount($account)) {
                            $sender->sendMessage($this->getMessage("unhide.fail"));
                            break;
                        }
                        $sender->sendMessage(str_replace("{{account}}", $account, $this->getMessage("unhide.success")));

                        break;

                    case "set":
                        $target = array_shift($args);
                        $amount = array_shift($args);
                        if (is_null($target) or is_null($amount)) {
                            $sender->sendMessage($this->getMessage("set.usage"));
                            break;
                        }
                        if (!$this->setMoney($target, $amount)) {
                            $sender->sendMessage($this->getMessage("set.fail"));
                            break;
                        }
                        $sender->sendMessage($this->getMessage("set.result.console"));
                        if (($player = $this->getServer()->getPlayer($target)) instanceof Player) {
                            $result = $this->getMessage("set.result.target");
                            $result = str_replace("{{money}}", $this->getFormattedMoney($amount), $result);
                            $player->sendMessage($result);
                        }
                        break;

                    case "grant":
                        $target = array_shift($args);
                        $amount = array_shift($args);
                        if (is_null($target) or is_null($amount)) {
                            $sender->sendMessage($this->getMessage("grant.usage"));
                            break;
                        }
                        if (!$this->grantMoney($target, $amount)) {
                            $sender->sendMessage($this->getMessage("grant.fail"));
                            break;
                        }
                        $sender->sendMessage($this->getMessage("grant.result.console"));
                        if (($player = $this->getServer()->getPlayer($target)) instanceof Player) {
                            $result = $this->getMessage("grant.result.target");
                            $result = str_replace("{{money}}", $this->getFormattedMoney($amount), $result);
                            $player->sendMessage($result);
                        }
                        break;

                    case "top":
                        $amount = array_shift($args);
                        if (is_null($amount)) {
                            $sender->sendMessage($this->getMessage("top.usage"));
                            break;
                        }
                        $sender->sendMessage($this->getMessage("top.title"));
                        $sender->sendMessage($this->getMessage("top.decoration"));
                        $rank = 1;
                        foreach ($this->getRanking($amount) as $name => $money) {
                            $item = $this->getMessage("top.item");
                            $item = str_replace("{{rank}}", $rank, $item);
                            $item = str_replace("{{name}}", $name, $item);
                            $item = str_replace("{{money}}", $this->getFormattedMoney($money), $item);
                            $sender->sendMessage($item);
                            $rank++;
                        }
                        $sender->sendMessage($this->getMessage("top.decoration"));
                        break;

                    case "stat":
                        $total = $this->getTotalMoney();
                        $accounts = $this->getNumberOfAccount();
                        $average = floor($total / $accounts);
                        $result = $this->getMessage("stat.result");
                        $result = str_replace("{{total}}", $this->getFormattedMoney($total), $result);
                        $result = str_replace("{{average}}", $average, $result);
                        $result = str_replace("{{accounts}}", $accounts, $result);
                        $sender->sendMessage($result);
                        break;

                    default:
                        $sender->sendMessage(str_replace("{{subCommand}}", $subCommand, $this->getMessage("system.error.invalidsubcommand")));
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
                        $sender->sendMessage($this->getFormattedMoney($money));
                        break;
                    case "help":
                        if ($sender->hasPermission("pocketmoney.help")) {
                            $sender->sendMessage("/money help");
                            $sender->sendMessage("/money view <account>");
                            $sender->sendMessage("/money pay <target>");
                            $sender->sendMessage("/money create <account>");
                            $sender->sendMessage("/money hide <account>");
                            $sender->sendMessage("/money unhide <account>");
                            $sender->sendMessage("/money wd <target> <amount>");
                            $sender->sendMessage("/money top <amount>");
                            $sender->sendMessage("/money stat");
                        } else {
                            $sender->sendMessage($this->getMessage("system.error.permission"));
                        }
                        break;

                    case "view":
                        if ($sender->hasPermission("pocketmoney.view")) {
                            $account = array_shift($args);
                            if (is_null($account)) {
                                $sender->sendMessage($this->getMessage("view.usage"));
                                break;
                            }

                            $money = $this->getMoney($account);
                            $type = $this->getType($account);
                            $hide = $this->getHide($account);
                            if ($money === false || $type === false || $hide === true) {
                                $sender->sendMessage($this->getMessage("view.fail"));
                                break;
                            }
                            $type = ($type === PlayerType::Player) ? $this->getMessage("view.type.player") : $this->getMessage("view.type.non-player");
                            $hide = ($hide === false) ? $this->getMessage("view.hide.false") : $this->getMessage("view.hide.true");
                            $result = $this->getMessage("view.result");
                            $result = str_replace("{{account}}", $account, $result);
                            $result = str_replace("{{money}}", $this->getFormattedMoney($money), $result);
                            $result = str_replace("{{type}}", $type, $result);
                            $result = str_replace("{{hide}}", $hide, $result);

                            $sender->sendMessage($result);
                        } else {
                            $sender->sendMessage($this->getMessage("system.error.permission"));
                        }
                        break;

                    case "pay":
                        if ($sender->hasPermission("pocketmoney.pay")) {
                            $target = array_shift($args);
                            $amount = array_shift($args);
                            if (is_null($target) or is_null($amount)) {
                                $sender->sendMessage($this->getMessage("pay.usage"));
                                break;
                            }
                            if (!$this->payMoney($sender->getName(), $target, $amount)) {
                                $sender->sendMessage($this->getMessage("pay.fail"));
                                break;
                            }
                            $formattedAmount = $this->getFormattedMoney($amount);
                            $senderMessage = $this->getMessage("pay.result.sender");
                            $senderMessage = str_replace("{{target}}", $target, $senderMessage);
                            $senderMessage = str_replace("{{money}}", $formattedAmount, $senderMessage);
                            $sender->sendMessage($senderMessage);
                            if (($targetPlayer = $this->getServer()->getPlayer($target)) instanceof Player) {
                                $targetMessage = $this->getMessage("pay.result.target");
                                $targetMessage = str_replace("{{sender}}", $sender->getName(), $targetMessage);
                                $targetMessage = str_replace("{{money}}", $formattedAmount, $targetMessage);
                                $targetPlayer->sendMessage($targetMessage);
                            }
                        } else {
                            $sender->sendMessage($this->getMessage("system.error.permission"));
                        }

                        break;

                    case "withdraw":
                    case "wd":
                        if ($sender->hasPermission("pocketmoney.withdraw")) {
                            $target = array_shift($args);
                            $amount = array_shift($args);
                            if (is_null($target) or is_null($amount)) {
                                $sender->sendMessage($this->getMessage("withdraw.usage"));
                                break;
                            }
                            $type = $this->getType($target);
                            if ($type === false) {
                                $sender->sendMessage($this->getMessage("withdraw.fail"));
                                break;
                            }
                            if ($type !== PlayerType::NonPlayer) {
                                $sender->sendMessage($this->getMessage("withdraw.error.nonplayer"));
                                break;
                            }
                            if (!$this->payMoney($target, $sender->getName(), $amount)) {
                                $sender->sendMessage($this->getMessage("withdraw.error.pay"));
                                break;
                            }
                            $result = $this->getMessage("withdraw.result");
                            $result = str_replace("{{target}}", $target, $result);
                            $result = str_replace("{{money}}", $this->getFormattedMoney($amount), $result);
                            $sender->sendMessage($result);
                        } else {
                            $sender->sendMessage($this->getMessage("system.error.permission"));
                        }
                        break;

                    case "create":
                        if ($sender->hasPermission("pocketmoney.create")) {
                            $account = array_shift($args);
                            if (is_null($account)) {
                                $sender->sendMessage($this->getMessage("create.usage"));
                                break;
                            }
                            if (!$this->createAccount($account)) {
                                $sender->sendMessage($this->getMessage("create.fail"));
                                break;
                            }
                            $sender->sendMessage(str_replace("{{account}}", $account, $this->getMessage("create.success")));
                        } else {
                            $sender->sendMessage($this->getMessage("system.error.permission"));
                        }
                        break;

                    case "hide":
                        if ($sender->hasPermission("pocketmoney.hide")) {
                            $account = array_shift($args);
                            if (is_null($account)) {
                                $sender->sendMessage($this->getMessage("hide.usage"));
                                break;
                            }
                            if (!$this->hideAccount($account)) {
                                $sender->sendMessage($this->getMessage("hide.fail"));
                                break;
                            }
                            $sender->sendMessage(str_replace("{{account}}", $account, $this->getMessage("hide.success")));

                        } else {
                            $sender->sendMessage($this->getMessage("system.error.permission"));
                        }

                        break;

                    case "unhide":
                    case "expose":
                        if ($sender->hasPermission("pocketmoney.unhide")) {
                            $account = array_shift($args);
                            if (is_null($account)) {
                                $sender->sendMessage($this->getMessage("unhide.usage"));
                                break;
                            }
                            if (!$this->unhideAccount($account)) {
                                $sender->sendMessage($this->getMessage("unhide.fail"));
                                break;
                            }
                            $sender->sendMessage(str_replace("{{account}}", $account, $this->getMessage("unhide.success")));

                        } else {
                            $sender->sendMessage($this->getMessage("system.error.permission"));
                        }

                        break;

                    case "top":
                        if ($sender->hasPermission("pocketmoney.top")) {
                            $amount = array_shift($args);
                            if (is_null($amount)) {
                                $sender->sendMessage($this->getMessage("top.usage"));
                                break;
                            }
                            $sender->sendMessage($this->getMessage("top.title"));
                            $sender->sendMessage($this->getMessage("top.decoration"));
                            $rank = 1;
                            foreach ($this->getRanking($amount) as $name => $money) {
                                $item = $this->getMessage("top.item");
                                $item = str_replace("{{rank}}", $rank, $item);
                                $item = str_replace("{{name}}", $name, $item);
                                $item = str_replace("{{money}}", $this->getFormattedMoney($money), $item);
                                $sender->sendMessage($item);
                                $rank++;
                            }
                            $sender->sendMessage($this->getMessage("top.decoration"));

                        } else {
                            $sender->sendMessage($this->getMessage("system.error.permission"));
                        }

                        break;
                    case "stat":
                        if ($sender->hasPermission("pocketmoney.stat")) {
                            $total = $this->getTotalMoney();
                            $accounts = $this->getNumberOfAccount();
                            $average = floor($total / $accounts);
                            $result = $this->getMessage("stat.result");
                            $result = str_replace("{{total}}", $this->getFormattedMoney($total), $result);
                            $result = str_replace("{{average}}", $average, $result);
                            $result = str_replace("{{accounts}}", $accounts, $result);
                            $sender->sendMessage($result);

                        } else {
                            $sender->sendMessage($this->getMessage("system.error.permission"));
                        }

                        break;

                    default:
                        $sender->sendMessage(str_replace("{{subCommand}}", $subCommand, $this->getMessage("system.error.invalidsubcommand")));
                        break;
                }
                return true;

            default:
                return false;
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event)
    {
        $username = $event->getPlayer()->getName();
        ;
        if ($this->createAccount($username, PlayerType::Player, false, $this->getDefaultMoney()) === true) {
            $this->getServer()->broadcastMessage("$username has been registered to PocketMoney");
        }
    }

    private function parseMessages(array $messages)
    {
        $result = [];
        foreach($messages as $key => $value){
            if(is_array($value)){
                foreach($this->parseMessages($value) as $k => $v){
                    $result[$key . "." . $k] = $v;
                }
            }else{
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function getMessage($key)
    {
        return isset($this->messages[$key]) ? $this->messages[$key] : $key;
    }

    private function getFormattedMoney($money)
    {
        return $money . " " . $this->system->get("currency");
    }
}
