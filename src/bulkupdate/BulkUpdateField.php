<?php

namespace MohamadRZ\NovaPerms\bulkupdate;

enum BulkUpdateField
{
    case NAME;
    case PERMISSION;
    case VALUE;
    case EXPIRY;
    case PRIMARY_GROUP;
}