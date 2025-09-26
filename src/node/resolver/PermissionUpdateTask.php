<?php

namespace MohamadRZ\NovaPerms\node\resolver;

use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class PermissionUpdateTask extends AsyncTask
{
    /**
     * @param string $playerName
     * @param string $serializedRootNodes
     * @param string $serializedAllKnownPermissions
     * @param string $serializedGroupPermissionsMap
     * @param string $serializedGroupInheritanceMap
     */
    public function __construct(
        private readonly string $playerName,
        private readonly string $serializedRootNodes,
        private readonly string $serializedAllKnownPermissions,
        private readonly string $serializedGroupPermissionsMap,
        private readonly string $serializedGroupInheritanceMap
    ) {}

    public function onRun(): void
    {
        /** @var array $rootNodes */
        $rootNodes = unserialize($this->serializedRootNodes);
        /** @var string[] $allKnownPermissions */
        $allKnownPermissions = unserialize($this->serializedAllKnownPermissions);
        /** @var array<string, array<string, bool>> $groupPermissionsMap */
        $groupPermissionsMap = unserialize($this->serializedGroupPermissionsMap);
        /** @var array<string, string[]> $groupInheritanceMap */
        $groupInheritanceMap = unserialize($this->serializedGroupInheritanceMap);

        $collected = [];
        $visitedGroups = [];

        $initialGroupsToVisit = [];
        foreach ($rootNodes as $nodeData) {
            if ($nodeData['type'] === 'inheritance') {
                $initialGroupsToVisit[] = $nodeData['group'];
            } else {
                $collected[$nodeData['key']] = $nodeData['value'];
            }
        }

        $stack = $initialGroupsToVisit;
        while (!empty($stack)) {
            $groupName = array_pop($stack);

            if (isset($visitedGroups[$groupName])) {
                continue;
            }
            $visitedGroups[$groupName] = true;

            if (isset($groupPermissionsMap[$groupName])) {
                foreach ($groupPermissionsMap[$groupName] as $perm => $value) {
                    if (!isset($collected[$perm])) {
                        $collected[$perm] = $value;
                    }
                }
            }

            if (isset($groupInheritanceMap[$groupName])) {
                foreach ($groupInheritanceMap[$groupName] as $parentGroup) {
                    if (!isset($visitedGroups[$parentGroup])) {
                        $stack[] = $parentGroup;
                    }
                }
            }
        }

        // قدم ۳: اعمال وایلدکاردها و عبارات باقاعده (Regex)
        $resolvedPermissions = $this->expandWildcardsAndRegex($collected, $allKnownPermissions);

        // قدم ۴: سریالایز کردن نتیجه نهایی برای ارسال امن به ترد اصلی
        $this->setResult(serialize($resolvedPermissions));
    }

    /**
     * این متد در ترد اصلی (main thread) پس از اتمام onRun اجرا می‌شود.
     * در اینجا می‌توان با خیال راحت از API های PocketMine-MP استفاده کرد.
     */
    public function onCompletion(): void
    {
        var_dump(1);
        $server = Server::getInstance();
        $plugin = $server->getPluginManager()->getPlugin("NovaPerms");

        /** @var array|false $currentPermissions */
        $currentPermissions = @unserialize($this->getResult());

        if (!is_array($currentPermissions)) {
            $server->getLogger()->warning("[NovaPerms] Failed to asynchronously resolve permissions for '{$this->playerName}'. Result was not a valid array.");
            return;
        }

        $user = $plugin->getUserManager()->getUser($this->playerName);

        $attachment = $user->getAttachment();
        if ($attachment === null) {
            $server->getLogger()->debug("[NovaPerms] Cannot apply permissions for '{$this->playerName}' because attachment is null.");
            return;
        }

        $lastAppliedPermissions = $user->getLastAppliedPermissions();

        $toAdd = array_diff_assoc($currentPermissions, $lastAppliedPermissions);
        $toRemove = array_diff_key($lastAppliedPermissions, $currentPermissions);

        foreach ($toRemove as $perm => $_) {
            $attachment->unsetPermission($perm);
        }

        foreach ($toAdd as $perm => $value) {
            var_dump($perm);
            $attachment->setPermission($perm, $value);
        }

        $user->setLastAppliedPermissions($currentPermissions);
        $server->getLogger()->debug("[NovaPerms] Asynchronously updated " . count($toAdd) . " added and " . count($toRemove) . " removed permissions for '{$this->playerName}'.");
    }

    /**
     * وایلدکاردها و عبارات باقاعده را به دسترسی‌های مشخص گسترش می‌دهد.
     * @param array<string, bool> $perms دسترسی‌های جمع‌آوری شده
     * @param string[] $knownPermissions لیست تمام دسترسی‌های موجود
     * @return array<string, bool> دسترسی‌های نهایی
     */
    private function expandWildcardsAndRegex(array $perms, array $knownPermissions): array
    {
        $expanded = [];
        $wildcards = [];

        // جدا کردن دسترسی‌های عادی از وایلدکاردها
        foreach($perms as $perm => $value) {
            if (str_ends_with($perm, '.*') || (str_starts_with($perm, '/') && str_ends_with($perm, '/'))) {
                $wildcards[$perm] = $value;
            } else {
                $expanded[$perm] = $value;
            }
        }

        if (empty($wildcards)) {
            return $expanded; // بهینه‌سازی: اگر وایلدکاردی وجود ندارد، محاسبات را انجام نده
        }

        // ساخت یک Trie برای جستجوی سریع پیشوندها
        $permissionTrie = new PermissionTrie();
        foreach ($knownPermissions as $perm) {
            $permissionTrie->insert($perm);
        }

        foreach ($wildcards as $perm => $value) {
            // حالت وایلدکارد (e.g., "myplugin.command.*")
            if (str_ends_with($perm, '.*')) {
                $prefix = substr($perm, 0, -2);
                foreach ($permissionTrie->getAllWithPrefix($prefix) as $known) {
                    if (!isset($expanded[$known])) { // دسترسی‌های مستقیم اولویت دارند
                        $expanded[$known] = $value;
                    }
                }
            }
            // حالت Regex (e.g., "/^essentials\.kit\..+/")
            elseif (str_starts_with($perm, '/') && str_ends_with($perm, '/')) {
                foreach ($knownPermissions as $known) {
                    if (@preg_match($perm, $known) === 1) {
                        if (!isset($expanded[$known])) { // دسترسی‌های مستقیم اولویت دارند
                            $expanded[$known] = $value;
                        }
                    }
                }
            }
        }

        return $expanded;
    }
}
