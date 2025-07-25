<?php

namespace MohamadRZ\StellarRanks\cache;

class UserCache extends BaseCache
{
    public function __construct()
    {
        parent::__construct('user');
    }

}