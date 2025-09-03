<?php

namespace MohamadRZ\NovaPerms\storage;

enum StorageTypes
{
case SQLite;
case MYSQL;
case YML;
}