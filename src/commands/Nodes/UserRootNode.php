<?php

namespace MohamadRZ\NovaPerms\commands;

use MohamadRZ\NovaPerms\model\TemporaryModifier;
use MohamadRZ\NovaPerms\model\User;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\node\Types\PermissionNode;
use MohamadRZ\NovaPerms\node\Types\InheritanceNode;
use MohamadRZ\NovaPerms\node\Types\MetaNode;
use MohamadRZ\NovaPerms\node\Types\PrefixNode;
use MohamadRZ\NovaPerms\node\Types\SuffixNode;
use MohamadRZ\NovaPerms\context\ImmutableContextSet;
use MohamadRZ\NovaPerms\utils\Duration;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class UserRootNode extends CommandNode
{
    public function getName(): string
    {
        return "user";
    }

    public function execute($sender, array $args): void
    {

        /*
        /lp user <user> info
        /lp user <user> permission
        /lp user <user> parent
        /lp user <user> meta
        /lp user <user> editor
        /lp user <user> promote <track> [context...]
        /lp user <user> demote <track> [context...]
        /lp user <user> showtracks
        /lp user <user> clear [context...]
        /lp user <user> clone <user>*/
        if (empty($args)) {
            $this->showHelp($sender);
            return;
        }

        $username = array_shift($args);
        $subCommand = array_shift($args);

        $plugin = NovaPermsPlugin::getInstance();
        $userManager = $plugin->getUserManager();
        $user = $userManager->getOrMake($username);

        switch (strtolower($subCommand)) {
            case "info":
                break;
            case "permission":
                $this->permissionHandler($user, $sender, $args);
                break;
            case "parent":
                $this->parentHandler($user, $sender, $args);
                break;
            case "meta":
                $this->metaHandler($user, $sender, $args);
                break;
            case "clear":
                break;
            case "clone":
                break;
            default:
                $this->showHelp($sender);
                break;
        }
    }

    public function showHelp(CommandSender $sender): void
    {
        $sender->sendMessage("/user <user> parent>");
    }



    private function parseContext(array $args): ImmutableContextSet
    {
        $builder = ImmutableContextSet::builder();

        foreach ($args as $arg) {
            if (strpos($arg, '=') !== false) {
                [$key, $value] = explode('=', $arg, 2);
                $builder->add($key, $value);
            }
        }

        return $builder->build();
    }

    private function contextMatches($nodeContext, $filterContext): bool
    {
        if ($nodeContext === null && $filterContext->isEmpty()) {
            return true;
        }

        if ($nodeContext === null || $filterContext->isEmpty()) {
            return false;
        }

        foreach ($filterContext->getContexts() as $context) {
            if (!$nodeContext->containsKey($context->getKey()) ||
                !in_array($context->getValue(), $nodeContext->getValues($context->getKey()))) {
                return false;
            }
        }

        return true;
    }

    private function permissionHandler(User $user, CommandSender $sender, array $args)
    {
        /*
        info
        set <node> <true/false> [context...]
        unset <node> [context...]
        settemp <node> <true/false> <duration> [temporary modifier] [context...]
        unsettemp <node> [duration] [context...]
        check <node>
        clear [context...]
         */
        if (empty($args)) {
            $this->showHelp($sender);
            return;
        }
        $subCommand = array_shift($args);
        switch (strtolower($subCommand)) {
            case "info":
                break;
            case "set":
                $node = array_shift($args);
                $value = array_shift($args) ?? true;
                $context = array_shift($args) ?? ImmutableContextSet::empty();

                $user->setNode(PermissionNode::builder($node)->value($value)->withContext($context)->build());
                break;
            case "unset":
                break;
            case "settemp":
                $node = array_shift($args);
                $value = array_shift($args) ?? true;
                $duration = $this->parseDurationString(array_shift($args));
                $temporaryModifier = array_shift($args) ?? TemporaryModifier::ACCUMULATE;
                $context = array_shift($args) ?? ImmutableContextSet::empty();

                if ($temporaryModifier === TemporaryModifier::REPLACE) {

                } elseif ($temporaryModifier === TemporaryModifier::DENY) {

                } else {

                }

                $user->setNode(PermissionNode::builder($node)->expiry($duration)->value($value)->withContext($context)->build());
                break;
            case "unsettemp":
                break;
            case "check":
                break;
            case "clear":
                break;
            default:
                break;
        }
    }

    function parseDurationString($duration): float|int
    {
        $pattern = '/(?:(\d+)y)?(?:(\d+)mo)?(?:(\d+)d)?(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?/';
        preg_match($pattern, strtolower($duration), $matches);

        $years   = isset($matches[1]) ? (int) $matches[1] : 0;
        $months  = isset($matches[2]) ? (int) $matches[2] : 0;
        $days    = isset($matches[3]) ? (int) $matches[3] : 0;
        $hours   = isset($matches[4]) ? (int) $matches[4] : 0;
        $minutes = isset($matches[5]) ? (int) $matches[5] : 0;
        $seconds = isset($matches[6]) ? (int) $matches[6] : 0;

        return $years   * 365 * 24 * 3600 +
            $months  * 30  * 24 * 3600 +
            $days    * 24  * 3600 +
            $hours   * 3600 +
            $minutes * 60 +
            $seconds;
    }

    private function parentHandler(User $user, CommandSender $sender, array $args): void
    {
        /*info
        set <group> [context...]
        add <group> [context...]
        remove <group> [context...]
        settrack <track> <index | group> [context...]
        addtemp <group> <duration> [temporary modifier] [context...]
        removetemp <group> [duration] [context...]
        clear [context...]
        cleartrack <track> [context...]
        switchprimarygroup <group>*/
        $subCommand = array_shift($args);
        switch (strtolower($subCommand)) {
            case "info":
                break;
            case "set":
                break;
            case "remove":
                break;
            case "addtemp":
                break;
            case "removetemp":
                break;
            case "clear":
                break;
            default:
                break;
        }
    }

    private function metaHandler(User $user, CommandSender $sender, array $args)
    {
        /*
        info
        set <key> <value> [context...]
        unset <key> [context...]
        settemp <key> <value> <duration> [temporary modifier] [context...]
        unsettemp <key> [context...]
        addprefix <priority> <prefix> [context...]
        addsuffix <priority> <suffix> [context...]
        setprefix [priority] <prefix> [context...]
        setsuffix [priority] <suffix> [context...]
        removeprefix <priority> [prefix] [context...]
        removesuffix <priority> [suffix] [context...]
        addtempprefix <priority> <prefix> <duration> [temporary modifier] [context...]
        addtempsuffix <priority> <suffix> <duration> [temporary modifier] [context...]
        settempprefix [priority] <prefix> <duration> [temporary modifier] [context...]
        settempsuffix [priority] <suffix> <duration> [temporary modifier] [context...]
        removetempprefix <priority> [prefix] [context...]
        removetempsuffix <priority> [suffix] [context...]
        clear [type] [context...]
         */
        $subCommand = array_shift($args);
        switch (strtolower($subCommand)) {
            case "info":
                break;
            case "set":
                break;
            case "unset":
                break;
            case "settemp":
                break;
            case "unsettemp":
                break;
            case "addprefix":
                break;
            case "addsuffix":
                break;
            case "setprefix":
                break;
            case "setsuffix":
                break;
            case "removeprefix":
                break;
            case "removesuffix":
                break;
            case "addtempprefix":
                break;
            case "addtempsuffix":
                break;
            case "settempprefix":
                break;
            case "settempsuffix":
                break;
            case "removetempprefix":
                break;
            case "removetempsuffix":
                break;
            case "clear":
                break;
            default:
                break;
        }
    }
}
