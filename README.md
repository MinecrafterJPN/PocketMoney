# PocketMoney

PocketMoney is the foundation of money system for PocketMine.

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

| Command | Parameter | Description |
| :-----: | :-------: | :---------: |
| /money | `None` | Show your money |
| /money help | `None` | Show help |
| /money view | `None` | Show balance of `<account>` |
| /money create | `<account>` | Open non-player `<account>` |
| /money wd | `<account>` `<amount>` | Withdraw `<amount>` from `<account>` |
| /money hide | `<account>` | Hide `<account>` from /money top |
| /money unhide | `<account>` | Unhide `<account>` from /money top |
| /money top | `<amount>` | Show the ranking up to `<amount>` |
| /money pay | `<target>` `<amount>` | Pay `<target>` `<amount>` |
| /money stat | `None` | Show current economy state (circulation, average money, number of account) |

# Tips

You can change the value of default money by rewriting system.yml

# For developers

See src/PocketMoney/PocketMoney.php
The first half of file is API.
If you have some questions about API, feel free to ask me in the forum or on Twitter.



