<?php

namespace MohamadRZ\NovaPerms\storage\implementations\file;

enum StorageLocation: string
{
    case USERS = 'users';
    case GROUPS = 'groups';
    case TRACKS = 'tracks';
    case MISC = 'misc';
}
