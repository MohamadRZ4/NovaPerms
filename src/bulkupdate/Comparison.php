<?php

namespace MohamadRZ\NovaPerms\bulkupdate;

enum Comparison
{
    case EQUAL;
    case NOT_EQUAL;
    case LIKE;
    case NOT_LIKE;
    case STARTS_WITH;
    case ENDS_WITH;
    case CONTAINS;
    case GREATER_THAN;
    case LESS_THAN;
}