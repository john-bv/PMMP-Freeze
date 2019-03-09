# freeze
A plugin for PMMP **3.0.0** - **3.6.4*** that allows staff to freeze players. [![](https://poggit.pmmp.io/shield.api/freeze)](https://poggit.pmmp.io/p/freeze)

## Permissions
 - `freeze` Allows all permission for the plugin.
 - `freeze.command` Allows usage of `/freeze`, `/thaw` and `/unfreeze`.
 - `freeze.immune` Disallows usage of `/freeze` with any player that has this permission.
 
## Commands
 - `/freeze <player>` - Freeze a player
 - `/thaw <player>` - Thaw or unfreeze a player
 - `/unfreeze <player>` - Thaw or unfreeze a player

## Config Explanations (See config for more details)
 - `autoban` - Auto bans players if they leave while frozen 
 - `autoban-msg` - The message that displays in chat when autobanned.
 - `format` - What you would like the freeze format to look like
 - `commands-frozen` - Allows players to use commands while frozen
 - `attack-frozen` - Allows the frozen player to be attacked.
 - `attacked-frozen` - Allow frozen player to be attacked.
 - `dms-frozen` - Allows frozen player to use dms while frozen.
 - `frozen-tag` - The tag that displays above a frozen player.
 - `frozen-msg` - Enable a message in chat when a player is frozen.
 - `format-tag` - The tag that displays above a player when frozen.
 - `title-msg` - The title that shows when a frozen player tries to move.
 - `action-msg` - The message above the hotbar when a frozen player tries to move.
