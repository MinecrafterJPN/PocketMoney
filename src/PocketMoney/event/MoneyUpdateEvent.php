<?php

namespace PocketMoney\event;

use pocketmine\Player;
use PocketMoney\PocketMoney;

class MoneyUpdateEvent extends PocketMoneyEvent
{
    const CAUSE_PAY = 0;
    const CAUSE_GRANT = 1;
    const CAUSE_SET = 2;

    public static $handlerList = null;

    /** @var string $player */
    private $player;
    /** @var int $amount */
    private $amount;
    /** @var int $cause */
    private $cause;

    /**
     * @param PocketMoney $plugin
     * @param string $player
     * @param int $amount
     * @param int $cause
     */
    public function __construct(PocketMoney $plugin, $player, $amount, $cause)
    {
        $this->player = $player;
        $this->amount = $amount;
        $this->cause = $cause;
        parent::__construct($plugin);
    }

    public function getPlayer()
    {
        return $this->player;
    }

    public function getAmount()
    {
        return $this->amount;
    }
}
