<?php

namespace hcf\systems\staffmode;

class Messages
{
    public const PREFIX = "§8[§6StaffMode§8] §r";
    public const NO_PERMISSION = self::PREFIX . "§cYou do not have permission to use this command.";
    public const NOT_IN_STAFF_MODE = self::PREFIX . "§cYou are not in staff mode.";
    public const ALREADY_IN_STAFF_MODE = self::PREFIX . "§cYou are already in staff mode.";
    public const ENTERED_STAFF_MODE = self::PREFIX . "§aYou have entered staff mode.";
    public const LEFT_STAFF_MODE = self::PREFIX . "§cYou have left staff mode.";
    public const STAFF_LIST = self::PREFIX . "§aStaff members online: %staffs";
    public const LINES = "§8§m--------------------";
    public const TELEPORT_STAFF = self::PREFIX. "§aTeleported to %p";

    # staff items
    public const SET_FROZEN = self::PREFIX . "§aYou have frozen to %p";
    public const SET_UNFROZEN = self::PREFIX . "§aYou have unfrozen %p";
    public const NOT_IN_FROZEN = self::PREFIX . "§cPlayer %p is not frozen.";
    public const NO_PERMISSION_ITEMS = self::PREFIX . "§cYou do not have permission to use staff items.";
    public const SET_VANISH = self::PREFIX . "§aYou have vanished.";
    public const SET_UNVANISH = self::PREFIX . "§aYou have unvanished.";

    #player
    public const ALREADY_FROZEN = self::PREFIX. "§cYou are already frozen.";
    public const UNFROZEN = self::PREFIX . "§aYou have been unfrozen by %p.";
}