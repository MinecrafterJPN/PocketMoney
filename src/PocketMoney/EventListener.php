<?php

namespace PocketMoney;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Server;
use PocketMoney\constants\PlayerType;

class EventListener implements Listener
{
    public function onPlayerJoin(PlayerJoinEvent $event)
    {
        $username = $event->getPlayer()->getName();
        if (PocketMoneyAPI::getAPI()->createAccount($username, PlayerType::Player, false) === true) {
            Server::getInstance()->broadcastMessage("$username has been registered to PocketMoney");
        }
    }
}