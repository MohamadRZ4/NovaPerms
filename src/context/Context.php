<?php

namespace MohamadRZ\NovaPerms\context;

class Context
{

    public static function builder(): ContextBuilder
    {
        return new ContextBuilder();
    }
}