<?php

namespace PocketMoney\event;

use pocketmine\Player;
use PocketMoney\PocketMoney;

class TransactionEvent extends PocketMoneyEvent
{
    const TRANSACTION_PAY = 0;

    public static $handlerList = null;

    /** @var string $sender */
    private $sender;
    /** @var string $receiver */
    private $receiver;
    /** @var int amount */
    private $amount;
    /** @var int $transactionType */
    private $transactionType;

    /**
     * @param PocketMoney $plugin
     * @param string $sender
     * @param string $receiver
     * @param int $amount
     * @param int $transactionType
     */
    public function __construct(PocketMoney $plugin, $sender, $receiver, $amount, $transactionType)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->amount = $amount;
        $this->transactionType = $transactionType;
        parent::__construct($plugin);
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function getReceiver()
    {
        return $this->receiver;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getTransactionType()
    {
        return $this->transactionType;
    }
} 