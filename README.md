# PocketMoney

PocketMoney is the PocketMine-MP plugin which provides economic system for your worlds.

# Installation
1.  Drop it into your /plugins folder.
2.  Restart your server.

# Console commands

| Command | Parameter | Description |
| :-----: | :-------: | :---------: |
| /money help | `None` | Show help |
| /money set | `<target>` `<amount>` | Set `<target>`'s money at `<amount>` |
| /money grant | `<target>` `<amount>` | Grant `<amount>` to `<target>` |

# Chat commands

| Command | Parameter | Description |
| :-----: | :-------: | :---------: |
| /money | `None` | Show your money |
| /money help | `None` | Show help |
| /money top | `<amount>` | Show the ranking up to `<amount>` |
| /money pay | `<target>` `<amount>` | Pay `<target>` `<amount>` |
| /money stat | `None` | Show current economy state (circulation, average money, number of account) |

# For developers

You can handle data of PocketMoney by coding as follow.

```php
$data = array(
  'issuer' => string Issuer(Optional), for example, your plugin name,
  'username' => string Target username,
  'method' => string Method Type( set / grant ),
  'amount' => int Amount
);

$this->api->dhandle("money.handle", $data);
```

----

You can get money of selected player by coding as follow.

```php
$data = array(
  'username' => string Target username
);

$money = $this->api->dhandle("money.player.get", $data);
```

----

You can get information in detail when money is changed by coding as follow.

```php
$this->api->addHandler("money.changed", array($this, "yourEventHandler"));
```

```php
$data = array(
  'issuer' => string Issuer('console', username or the classname which use "money.handle"),
  'target' => string Target,
  'method' => string Method('set', 'grant' or 'pay'),
  'amount' => integer Amount
);
```


