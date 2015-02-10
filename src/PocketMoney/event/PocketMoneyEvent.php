<?php

namespace PocketMoney\event;

use pocketmine\event\plugin\PluginEvent;
use PocketMoney\PocketMoney;

class PocketMoneyEvent extends PluginEvent
{
    public function __construct(PocketMoney $plugin)
    {
        parent::__construct($plugin);
    }
} 