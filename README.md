# PocketMoney

The core economy system with many APIs

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
| /money unhide | `<account>` | Unhide `<account>` from /money top |
| /money set | `<target>` `<amount>` | Set `<target>`'s money at `<amount>` |
| /money grant | `<target>` `<amount>` | Grant `<amount>` to `<target>` |
| /money top | `<amount>` | Show the ranking up to `<amount>` |
| /money stat | `None` | Show current economy state (circulation, average money, number of account) |

# Chat commands

| Command | Parameter | Description | Permission |
| :-----: | :-------: | :---------: | :---------: |
| /money | `None` | Show your money | pocketmoney.money |
| /money help | `None` | Show help | pocketmoney.help |
| /money view | `None` | Show balance of `<account>` | pocketmoney.view |
| /money create | `<account>` | Open non-player `<account>` | pocketmoney.create |
| /money wd | `<account>` `<amount>` | Withdraw `<amount>` from `<account>` | pocketmoney.withdraw |
| /money hide | `<account>` | Hide `<account>` from /money top | pocketmoney.hide |
| /money unhide | `<account>` | Unhide `<account>` from /money top | pocketmoney.unhide |
| /money top | `<amount>` | Show the ranking up to `<amount>` | pocketmoney.top |
| /money pay | `<target>` `<amount>` | Pay `<target>` `<amount>` | pocketmoney.pay | 
| /money stat | `None` | Show current economy state (circulation, average money, number of account) | pocketmoney.stat |

# Customize
## Change messages PocketMoney sends
1. Open "plugins/PocketMoney/messages.yml"
2. Change messages

## Change default money(default: 500)
1. Open "plugins/PocketMoney/system.yml"
2. Change "default\_money"

## Change currency(default: PM)
1. Open "plugins/PocketMoney/ssystem.yml"
2. Change "currency"

# For developers

See src/PocketMoney/PocketMoney.php
The first half of file is API.

## Events
- MoneyUpdateEvent: called when someone's money is changed.
- TransactionEvent: called when transaction(pay, withdraw) is performed.

If you have some questions about API, feel free to ask me in the forum or on Twitter.



