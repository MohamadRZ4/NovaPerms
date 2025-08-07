<?php

namespace MohamadRZ\NovaPerms\model;

enum TemporaryModifier: string
{
case ACCUMULATE = "accumulate";
case REPLACE = "replace";
case DENY  = "deny";
}