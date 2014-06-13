<?php

namespace PocketMoney;

class PocketMoneyAPI
{

	private static $configRef = null;
	private static $systemRef = null;

	public static function init(&$configRef, &$systemRef)
	{
		self::configReference = $configRef;
		self::systemRef = $systemRef;
	}

	public static function getMoney($account)
	{
		
	}

	public static function getType($account)
	{

	}

	public static function setMoney($target, $amount)
	{

	}

	public static function grantMoney($target, $amoutn)
	{

	}
}