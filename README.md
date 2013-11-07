# PocketMoney

PocketMoney is the PocketMine-MP plugin which provides economic system for your worlds.

# Installation
1.  Drop it into your /plugins folder.
2.  Restart your server.

# Console commands

| Command | Parameter | Description |
| :-----: | :-------: | :---------: |
| /money help | `None` | Show help |
| /money create | `<accountname>` | Open account |
| /money set | `<target>` `<amount>` | Set `<target>`'s money at `<amount>` |
| /money grant | `<target>` `<amount>` | Grant `<amount>` to `<target>` |
| /money top | `<amount>` | Show the ranking up to `<amount>` |
| /money stat | `None` | Show current economy state (circulation, average money, number of account) |

# Chat commands

| Command | Parameter | Description |
| :-----: | :-------: | :---------: |
| /money | `None` | Show your money |
| /money help | `None` | Show help |
| /money create | `<accountname>` | Open account |
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

You can open an account by coding as follow.

```php
$data = array(
  'account' => string account name
);

$this->api->dhandle("money.create.account", $data);
```



