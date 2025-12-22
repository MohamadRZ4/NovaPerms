<?php

namespace MohamadRZ\NovaPerms\bulkupdate;

class BulkUpdateStatistics
{
    public int $affectedUsers = 0;
    public int $affectedGroups = 0;
    public int $totalOperations = 0;
    public float $executionTime = 0.0;

    public function getTotalAffected(): int
    {
        return $this->affectedUsers + $this->affectedGroups;
    }
}