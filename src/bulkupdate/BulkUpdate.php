<?php

namespace MohamadRZ\NovaPerms\bulkupdate;

use MohamadRZ\NovaPerms\bulkupdate\action\BulkAction;

class BulkUpdate
{
    private DataType $dataType = DataType::ALL;
    private ?BulkAction $action = null;
    private array $filters = [];
    private bool $trackStatistics = true;

    public function getDataType(): DataType { return $this->dataType; }
    public function setDataType(DataType $dataType): void { $this->dataType = $dataType; }
    public function getAction(): ?BulkAction { return $this->action; }
    public function setAction(BulkAction $action): void { $this->action = $action; }
    public function getFilters(): array { return $this->filters; }
    public function addFilter(FilterConstraint $filter): void { $this->filters[] = $filter; }
    public function isTrackStatistics(): bool { return $this->trackStatistics; }
    public function setTrackStatistics(bool $track): void { $this->trackStatistics = $track; }

    public function buildQueries(): array
    {
        if ($this->action === null) {
            throw new \InvalidArgumentException("Action must be set");
        }

        $queries = [];
        $params = [];
        $whereConditions = [];

        foreach ($this->filters as $filter) {
            $condition = $this->buildFilterCondition($filter, $params);
            if ($condition !== null) {
                $whereConditions[] = $condition;
            }
        }

        $whereClause = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';

        if ($this->dataType === DataType::USERS) {
            $queries[] = $this->buildQueryForTable('UserPermissions', 'username', $whereClause, $params);
        } elseif ($this->dataType === DataType::GROUPS) {
            $queries[] = $this->buildQueryForTable('GroupPermissions', 'group_name', $whereClause, $params);
        } elseif ($this->dataType === DataType::ALL) {
            $queries[] = $this->buildQueryForTable('UserPermissions', 'username', $whereClause, $params);
            $queries[] = $this->buildQueryForTable('GroupPermissions', 'group_name', $whereClause, $params);
        }

        return $queries;
    }

    private function buildQueryForTable(string $table, string $ownerColumn, string $whereClause, array $params): array
    {
        $actionType = $this->action->getType();
        $actionData = $this->action->getData();

        $sql = match ($actionType) {
            'delete' => $this->buildDeleteQuery($table, $whereClause),
            'update' => $this->buildUpdateQuery($table, $whereClause, $actionData, $params),
            'upsert' => $this->buildUpsertQuery($table, $ownerColumn, $whereClause, $actionData, $params),
            'update_primary_group' => $this->buildUpdatePrimaryGroupQuery($whereClause, $actionData, $params),
            default => throw new \InvalidArgumentException("Unknown action type: " . $actionType)
        };

        return ['sql' => $sql, 'params' => $params, 'table' => $table];
    }

    private function buildFilterCondition(FilterConstraint $filter, array &$params): ?string
    {
        $column = match ($filter->field) {
            BulkUpdateField::NAME => 'username',
            BulkUpdateField::PERMISSION => 'permission',
            BulkUpdateField::VALUE => 'value',
            BulkUpdateField::EXPIRY => 'expiry',
            BulkUpdateField::PRIMARY_GROUP => 'primary_group',
        };

        $paramName = $column . '_' . count($params);

        switch ($filter->comparison) {
            case Comparison::EQUAL:
                $params[$paramName] = $filter->value;
                return "{$column} = :{$paramName}";
            case Comparison::NOT_EQUAL:
                $params[$paramName] = $filter->value;
                return "{$column} != :{$paramName}";
            case Comparison::LIKE:
                $params[$paramName] = $filter->value;
                return "{$column} LIKE :{$paramName}";
            case Comparison::NOT_LIKE:
                $params[$paramName] = $filter->value;
                return "{$column} NOT LIKE :{$paramName}";
            case Comparison::STARTS_WITH:
                $params[$paramName] = $filter->value . '%';
                return "{$column} LIKE :{$paramName}";
            case Comparison::ENDS_WITH:
                $params[$paramName] = '%' . $filter->value;
                return "{$column} LIKE :{$paramName}";
            case Comparison::CONTAINS:
                $params[$paramName] = '%' . $filter->value . '%';
                return "{$column} LIKE :{$paramName}";
            case Comparison::GREATER_THAN:
                $params[$paramName] = $filter->value;
                return "{$column} > :{$paramName}";
            case Comparison::LESS_THAN:
                $params[$paramName] = $filter->value;
                return "{$column} < :{$paramName}";
        }

        return null;
    }

    private function buildDeleteQuery(string $table, string $whereClause): string
    {
        return "DELETE FROM {$table}{$whereClause}";
    }

    private function buildUpdateQuery(string $table, string $whereClause, array $actionData, array &$params): string
    {
        $setClauses = [];

        if (isset($actionData['value']) && $actionData['value'] !== null) {
            $params['update_value'] = $actionData['value'];
            $setClauses[] = "value = :update_value";
        }

        if (isset($actionData['expiry']) && $actionData['expiry'] !== null) {
            $params['update_expiry'] = $actionData['expiry'];
            $setClauses[] = "expiry = :update_expiry";
        }

        if (empty($setClauses)) {
            throw new \InvalidArgumentException("No fields to update");
        }

        return "UPDATE {$table} SET " . implode(', ', $setClauses) . $whereClause;
    }

    private function buildUpsertQuery(string $table, string $ownerColumn, string $whereClause, array $actionData, array &$params): string
    {
        if (!isset($actionData['permission'])) {
            throw new \InvalidArgumentException("Permission must be specified for upsert");
        }

        $params['upsert_permission'] = $actionData['permission'];
        $params['upsert_value'] = ($actionData['value'] ?? true) ? 1 : 0;
        $params['upsert_expiry'] = -1;

        $sourceTable = ($table === 'UserPermissions') ? 'Users' : 'Groups';
        $sourceColumn = ($table === 'UserPermissions') ? 'username' : 'name';

        if (!empty($whereClause)) {
            return "INSERT OR REPLACE INTO {$table} ({$ownerColumn}, permission, value, expiry)
                    SELECT {$sourceColumn}, :upsert_permission, :upsert_value, :upsert_expiry
                    FROM {$sourceTable}
                    WHERE {$sourceColumn} IN (
                        SELECT DISTINCT {$ownerColumn} FROM {$table}{$whereClause}
                    )";
        }

        return "INSERT OR REPLACE INTO {$table} ({$ownerColumn}, permission, value, expiry)
                SELECT {$sourceColumn}, :upsert_permission, :upsert_value, :upsert_expiry
                FROM {$sourceTable}";
    }

    private function buildUpdatePrimaryGroupQuery(string $whereClause, array $actionData, array &$params): string
    {
        $params['primary_group'] = $actionData['group_name'];
        return "UPDATE Users SET primary_group = :primary_group{$whereClause}";
    }

    public function shouldUpdateUsers(): bool
    {
        return $this->dataType === DataType::USERS || $this->dataType === DataType::ALL;
    }

    public function shouldUpdateGroups(): bool
    {
        return $this->dataType === DataType::GROUPS || $this->dataType === DataType::ALL;
    }
}