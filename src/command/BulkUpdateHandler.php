<?php

namespace MohamadRZ\NovaPerms\command;

use MohamadRZ\NovaPerms\bulkupdate\action\DeleteAction;
use MohamadRZ\NovaPerms\bulkupdate\action\UpdateAction;
use MohamadRZ\NovaPerms\bulkupdate\BulkUpdateBuilder;
use MohamadRZ\NovaPerms\bulkupdate\BulkUpdateField;
use MohamadRZ\NovaPerms\bulkupdate\BulkUpdateStatistics;
use MohamadRZ\NovaPerms\bulkupdate\Comparison;
use MohamadRZ\NovaPerms\bulkupdate\DataType;
use MohamadRZ\NovaPerms\bulkupdate\UpdatePrimaryGroupAction;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\scheduler\Task;

class BulkUpdateHandler
{
    public const PREFIX = "§7[§l§bN§3P§4§r§7]§r";
    private array $pendingBulkUpdates = [];

    public function bulkUpdateHandler(CommandSender $sender, array $args): void
    {
        if (count($args) > 0 && strtolower($args[0]) === 'confirm') {
            $this->confirmBulkUpdate($sender, $args[1] ?? null);
            return;
        }

        if (!($sender instanceof ConsoleCommandSender)) {
            $sender->sendMessage(self::PREFIX . " §c✗ Console only! Reason: Can cause irreversible damage.");
            return;
        }

        if (count($args) < 2) {
            $this->sendUsage($sender);
            return;
        }

        $dataType = match (strtolower(array_shift($args))) {
            'all' => DataType::ALL,
            'users', 'user' => DataType::USERS,
            'groups', 'group' => DataType::GROUPS,
            default => null
        };

        if ($dataType === null) {
            $sender->sendMessage(self::PREFIX . " §cInvalid type. Use: §eall§7, §eusers§7, §egroups");
            return;
        }

        $actionStr = strtolower(array_shift($args));
        if (!in_array($actionStr, ['update', 'delete'])) {
            $sender->sendMessage(self::PREFIX . " §cInvalid action. Use: §eupdate§7, §edelete");
            return;
        }

        try {
            $builder = BulkUpdateBuilder::create()->dataType($dataType)->trackStatistics(true);

            if ($actionStr === 'update') {
                $this->handleUpdate($sender, $args, $builder, $dataType, $actionStr);
            } else {
                $this->handleDelete($sender, $args, $builder, $dataType, $actionStr);
            }
        } catch (\Exception $e) {
            $sender->sendMessage(self::PREFIX . " §cError: " . $e->getMessage());
        }
    }

    private function handleUpdate(CommandSender $sender, array $args, BulkUpdateBuilder $builder, DataType $dataType, string $action): void
    {
        if (count($args) < 2) {
            $sender->sendMessage(self::PREFIX . " §cUsage: §e/np bulkupdate <type> update <field> <value> [constraints]");
            return;
        }

        $field = strtolower(array_shift($args));
        $value = array_shift($args);

        if (!in_array($field, ['permission', 'value', 'expiry', 'primarygroup', 'primary-group'])) {
            $sender->sendMessage(self::PREFIX . " §cInvalid field: §e{$field}");
            return;
        }

        $updateAction = UpdateAction::create();

        if ($field === 'permission') {
            $permName = $value;
            if (count($args) < 1) {
                $permValue = true;
            } else {
                $permValue = array_shift($args);
            }

            $bool = $this->parseBool($permValue);

            if ($bool === null) {
                $sender->sendMessage(self::PREFIX . " §cInvalid value: §e{$permValue}");
                return;
            }

            $updateAction->setPermission($permName)->setValue($bool);
            $builder->action($updateAction);

        } elseif ($field === 'value') {
            $bool = $this->parseBool($value);
            if ($bool === null) {
                $sender->sendMessage(self::PREFIX . " §cInvalid boolean: §e{$value}");
                return;
            }
            $updateAction->setValue($bool);
            $builder->action($updateAction);

        } elseif ($field === 'expiry') {
            if (!is_numeric($value)) {
                $sender->sendMessage(self::PREFIX . " §cExpiry must be numeric (-1 for permanent)");
                return;
            }
            $updateAction->setExpiry((int)$value);
            $builder->action($updateAction);

        } else {
            $builder->action(UpdatePrimaryGroupAction::create($value));
        }

        $this->parseConstraints($sender, $args, $builder);
        $this->showConfirm($sender, $builder, $dataType, $action);
    }

    private function handleDelete(CommandSender $sender, array $args, BulkUpdateBuilder $builder, DataType $dataType, string $action): void
    {
        $builder->action(DeleteAction::create());

        if (empty($args)) {
            $sender->sendMessage(self::PREFIX . " §cDelete requires at least one constraint!");
            return;
        }

        $this->parseConstraints($sender, $args, $builder);
        $this->showConfirm($sender, $builder, $dataType, $action);
    }

    private function parseConstraints(CommandSender $sender, array $args, BulkUpdateBuilder $builder): void
    {
        foreach ($args as $constraint) {
            if (!preg_match('/^([a-z\-]+)\s*(==|!=|~~|!~)\s*(.+)$/i', $constraint, $m)) {
                $sender->sendMessage(self::PREFIX . " §cInvalid constraint: §e{$constraint}");
                continue;
            }

            $field = match (strtolower(trim($m[1]))) {
                'permission' => BulkUpdateField::PERMISSION,
                'value' => BulkUpdateField::VALUE,
                'expiry', 'expire' => BulkUpdateField::EXPIRY,
                'name', 'username' => BulkUpdateField::NAME,
                'primarygroup', 'primary-group' => BulkUpdateField::PRIMARY_GROUP,
                default => null
            };

            $comparison = match (trim($m[2])) {
                '==' => Comparison::EQUAL,
                '!=' => Comparison::NOT_EQUAL,
                '~~' => Comparison::LIKE,
                '!~' => Comparison::NOT_LIKE,
                default => null
            };

            if ($field === null || $comparison === null) {
                $sender->sendMessage(self::PREFIX . " §cInvalid constraint syntax");
                continue;
            }

            $value = $this->parseConstraintValue($field, trim($m[3]), $comparison);
            $builder->filter($field, $comparison, $value);
        }
    }

    private function parseConstraintValue(BulkUpdateField $field, string $value, Comparison $comparison): mixed
    {
        if ($comparison === Comparison::LIKE || $comparison === Comparison::NOT_LIKE) {
            return $value;
        }

        return match ($field) {
            BulkUpdateField::VALUE => $this->parseBool($value) ?? false,
            BulkUpdateField::EXPIRY => is_numeric($value) ? (int)$value : -1,
            default => $value
        };
    }

    private function parseBool(string $value): ?bool
    {
        return match (strtolower($value)) {
            'true', 'yes', '1' => true,
            'false', 'no', '0' => false,
            default => null
        };
    }

    private function showConfirm(CommandSender $sender, BulkUpdateBuilder $builder, DataType $dataType, string $action): void
    {
        $code = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));

        $typeStr = match($dataType) {
            DataType::ALL => "ALL",
            DataType::USERS => "USERS",
            DataType::GROUPS => "GROUPS"
        };

        $sender->sendMessage("");
        $sender->sendMessage(self::PREFIX . " §c§l⚠ WARNING ⚠");
        $sender->sendMessage(self::PREFIX . " §cIrreversible bulk operation!");
        $sender->sendMessage(self::PREFIX . " §7Type: §e{$typeStr} §7| Action: §e" . strtoupper($action));
        $sender->sendMessage("");
        $sender->sendMessage(self::PREFIX . " §6Confirm: §e/np bulkupdate confirm {$code}");
        $sender->sendMessage(self::PREFIX . " §7Expires in 30s");
        $sender->sendMessage("");

        $this->storePending($code, $builder);
    }

    private function storePending(string $code, BulkUpdateBuilder $builder): void
    {
        $this->pendingBulkUpdates[$code] = ['builder' => $builder, 'expires' => time() + 30];

        NovaPermsPlugin::getInstance()->getScheduler()->scheduleDelayedTask(
            new class($this, $code) extends Task {
                public function __construct(private $handler, private string $code) {}
                public function onRun(): void {
                    $this->handler->cleanupPending($this->code);
                }
            },
            600
        );
    }

    public function cleanupPending(string $code): void
    {
        unset($this->pendingBulkUpdates[$code]);
    }

    public function confirmBulkUpdate(CommandSender $sender, ?string $code): void
    {
        if ($code === null) {
            $sender->sendMessage(self::PREFIX . " §cUsage: /np bulkupdate confirm <code>");
            return;
        }

        if (!isset($this->pendingBulkUpdates[$code]) || time() > $this->pendingBulkUpdates[$code]['expires']) {
            $sender->sendMessage(self::PREFIX . " §cInvalid or expired code!");
            return;
        }

        $builder = $this->pendingBulkUpdates[$code]['builder'];
        unset($this->pendingBulkUpdates[$code]);

        $this->execute($sender, $builder);
    }

    private function execute(CommandSender $sender, BulkUpdateBuilder $builder): void
    {
        $sender->sendMessage(self::PREFIX . " §eExecuting bulk operation...");
        $startTime = microtime(true);

        try {
            $operation = $builder->build();

            NovaPermsPlugin::getInstance()->getStorage()->applyBulkUpdate($operation)
                ->onCompletion(
                    function(BulkUpdateStatistics $stats) use ($sender, $startTime) {
                        $time = round(microtime(true) - $startTime, 3);

                        $sender->sendMessage("");
                        $sender->sendMessage(self::PREFIX . " §a✓ Bulk update completed!");

                        if ($stats->affectedUsers > 0) {
                            $sender->sendMessage(self::PREFIX . " §7Users: §b{$stats->affectedUsers}");
                        }
                        if ($stats->affectedGroups > 0) {
                            $sender->sendMessage(self::PREFIX . " §7Groups: §b{$stats->affectedGroups}");
                        }

                        $sender->sendMessage(self::PREFIX . " §7Time: §b{$time}s");
                        $sender->sendMessage("");
                    },
                    function() use ($sender) {
                        $sender->sendMessage("");
                        $sender->sendMessage(self::PREFIX . " §c✗ Bulk update failed! Check console.");
                        $sender->sendMessage("");
                    }
                );

        } catch (\Exception $e) {
            $sender->sendMessage(self::PREFIX . " §c✗ Error: " . $e->getMessage());
        }
    }

    private function sendUsage(CommandSender $sender): void
    {
        $sender->sendMessage("");
        $sender->sendMessage(self::PREFIX . " §6§lBulk Update");
        $sender->sendMessage("");
        $sender->sendMessage(" §e/np bulkupdate §7<type> <action> <field> <value> [constraints]");
        $sender->sendMessage("");
        $sender->sendMessage(" §7Types: §eall §7| §eusers §7| §egroups");
        $sender->sendMessage(" §7Actions: §eupdate §7| §edelete");
        $sender->sendMessage("");
        $sender->sendMessage(" §bUpdate Fields:");
        $sender->sendMessage("  §epermission §8<name> <true/false> §7- Add/update permission");
        $sender->sendMessage("  §evalue §8<true/false> §7- Change existing perms value");
        $sender->sendMessage("  §eexpiry §8<timestamp> §7- Change expiry (-1 = permanent)");
        $sender->sendMessage("  §eprimarygroup §8<group> §7- Change primary group");
        $sender->sendMessage("");
        $sender->sendMessage(" §bConstraints: §7\"field operator value\"");
        $sender->sendMessage("  §7Fields: §epermission §7| §evalue §7| §eexpiry §7| §ename §7| §eprimarygroup");
        $sender->sendMessage("  §7Operators: §e== §7| §e!= §7| §e~~ §7(like) | §e!~ §7(not like)");
        $sender->sendMessage("  §7Wildcards: §e% §7(any chars) | §e_ §7(one char)");
        $sender->sendMessage("");
        $sender->sendMessage(" §aExamples:");
        $sender->sendMessage(" §7Add perm to all users:");
        $sender->sendMessage("  §e/np bulkupdate users update permission group.default true");
        $sender->sendMessage("");
        $sender->sendMessage(" §7Add perm to specific user:");
        $sender->sendMessage("  §e/np bulkupdate users update permission fly true \"name == Steve\"");
        $sender->sendMessage("");
        $sender->sendMessage(" §7Enable all admin perms:");
        $sender->sendMessage("  §e/np bulkupdate all update value true \"permission ~~ admin.%\"");
        $sender->sendMessage("");
        $sender->sendMessage(" §7Delete old plugin perms:");
        $sender->sendMessage("  §e/np bulkupdate all delete \"permission ~~ oldplugin.%\"");
        $sender->sendMessage("");
        $sender->sendMessage(" §7Make perm permanent:");
        $sender->sendMessage("  §e/np bulkupdate all update expiry -1 \"permission == event.perm\"");
        $sender->sendMessage("");
        $sender->sendMessage(" §c⚠ WARNING: Always backup before bulk operations!");
        $sender->sendMessage("");
    }
}