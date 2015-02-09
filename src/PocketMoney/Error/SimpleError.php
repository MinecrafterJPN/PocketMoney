<?php

namespace PocketMoney\error;

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

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getErrorNumber()
    {
        return $this->errorNumber;
    }
}