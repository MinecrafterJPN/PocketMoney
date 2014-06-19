<?php

namespace PocketMoney\Error;

class SimpleError
{
    const AccountNotExist = 0;
    const MoneyNotEnough = 1;
    const InvalidAmount = 2;
    const AccountAlreadyExist = 3;

    const Other = -1;

    private $errorNumber, $description;

    public function __construct($errorNumber, $description)
    {
        $this->errorNumber = $errorNumber;
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getErrorNumber()
    {
        return $this->errorNumber;
    }
}