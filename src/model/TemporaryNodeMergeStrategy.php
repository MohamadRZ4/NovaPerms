<?php

namespace MohamadRZ\NovaPerms\model;

enum TemporaryNodeMergeStrategy: string
{
    case ACCUMULATE = "accumulate";
    case REPLACE = "replace";
    case DENY = "deny";
}
