<?php

namespace MohamadRZ\NovaPerms\storage;

final class DataConstraints {

    private function __construct() {}

    public const MAX_PERMISSION_LENGTH = 200;

    public const MAX_TRACK_NAME_LENGTH = 36;
    public const MAX_GROUP_NAME_LENGTH = 36;

    public const MAX_PLAYER_USERNAME_LENGTH = 16;
    public const PLAYER_USERNAME_INVALID_CHAR_PATTERN = '/[^A-Za-z0-9_]/';

    public const MAX_SERVER_LENGTH = 36;
    public const MAX_WORLD_LENGTH = 36;

    public static function permissionTest(string $s) : bool {
        return $s !== '' && strlen($s) <= self::MAX_PERMISSION_LENGTH;
    }

    public static function playerUsernameTest(string $s) : bool {
        return $s !== '' &&
            strlen($s) <= self::MAX_PLAYER_USERNAME_LENGTH &&
            !preg_match(self::PLAYER_USERNAME_INVALID_CHAR_PATTERN, $s);
    }

    public static function playerUsernameTestLenient(string $s) : bool {
        return $s !== '' && strlen($s) <= self::MAX_PLAYER_USERNAME_LENGTH;
    }

    public static function groupNameTest(string $s) : bool {
        return $s !== '' &&
            strlen($s) <= self::MAX_GROUP_NAME_LENGTH &&
            strpos($s, ' ') === false;
    }

    public static function groupNameTestAllowSpace(string $s) : bool {
        return $s !== '' && strlen($s) <= self::MAX_GROUP_NAME_LENGTH;
    }

    public static function trackNameTest(string $s) : bool {
        return $s !== '' &&
            strlen($s) <= self::MAX_TRACK_NAME_LENGTH &&
            strpos($s, ' ') === false;
    }

    public static function trackNameTestAllowSpace(string $s) : bool {
        return $s !== '' && strlen($s) <= self::MAX_TRACK_NAME_LENGTH;
    }

    public static function serverNameTest(string $s) : bool {
        return $s !== '' &&
            strlen($s) <= self::MAX_SERVER_LENGTH &&
            strpos($s, ' ') === false;
    }

    public static function worldNameTest(string $s) : bool {
        return $s !== '' && strlen($s) <= self::MAX_WORLD_LENGTH;
    }
}