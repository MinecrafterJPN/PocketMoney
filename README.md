# PocketMoney

PocketMoney is the PocketMine-MP plugin which provides economic system for your worlds.

# Installation
1.  Drop it into your /plugins folder.
2.  Restart your server.

# Console commands

| Command | Parameter | Description |
| :-----: | :-------: | :---------: |
| /money help | `None` | Show help |
| /money view | `<account>` | Show balance of `<account>` |
| /money create | `<account>` | Open non-player `<account>` |
| /money hide | `<account>` | Hide `<account>` from /money top |
| /money set | `<target>` `<amount>` | Set `<target>`'s money at `<amount>` |
| /money grant | `<target>` `<amount>` | Grant `<amount>` to `<target>` |
| /money top | `<amount>` | Show the ranking up to `<amount>` |
| /money stat | `None` | Show current economy state (circulation, average money, number of account) |

# Chat commands

| Command | Parameter | Description |
| :-----: | :-------: | :---------: |
| /money | `None` | Show your money |
| /money help | `None` | Show help |
| /money view | `None` | Show balance of `<account>` |
| /money create | `<account>` | Open non-player `<account>` |
| /money wd | `<account>` `<amount>` | Withdraw `<amount>` from `<account>` |
| /money hide | `<account>` | Hide `<account>` from /money top |
| /money top | `<amount>` | Show the ranking up to `<amount>` |
| /money pay | `<target>` `<amount>` | Pay `<target>` `<amount>` |
| /money stat | `None` | Show current economy state (circulation, average money, number of account) |

# For developers

You can handle data of PocketMoney by coding as follow.

```php
$data = array(
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

You can open non-player account by coding as follow.

```php
$data = array(
  'account' => string account name,
  'hide' => int hidden flag, for example, if you want to hide, the value is 1. If not, 0.
);

$this->api->dhandle("money.create.account", $data);
```




