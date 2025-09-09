<?php

namespace MohamadRZ\NovaPerms\commands\args;

use CortexPE\Commando\args\BaseArgument;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;

class GroupArgument extends BaseArgument {

    public function __construct(bool $optional = false, ?string $name = null) {
        $name = $name ?? "group";
        parent::__construct($name, $optional);

        $this->applyGroupEnum();
    }

    public function getTypeName(): string {
        return "group";
    }

    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_TYPE_STRING;
    }

    public function canParse(string $testString, CommandSender $sender): bool {
        foreach (NovaPermsPlugin::getGroupManager()->getAllGroups() as $group) {
            if (strcasecmp($testString, $group->getName()) === 0) {
                return true;
            }
        }
        return false;
    }

    public function parse(string $argument, CommandSender $sender): string {
        foreach (NovaPermsPlugin::getGroupManager()->getAllGroups() as $group) {
            if (strcasecmp($argument, $group->getName()) === 0) {
                return $group->getName();
            }
        }
        return $argument;
    }

    private function applyGroupEnum(): void {
        $groupNames = [];
        foreach (NovaPermsPlugin::getGroupManager()->getAllGroups() as $group) {
            $groupNames[] = $group->getName();
        }

        $enum = new CommandEnum($this->getName(), $groupNames);
        $param = $this->getNetworkParameterData();

        $param->enum = $enum;
        $param->paramType = AvailableCommandsPacket::ARG_FLAG_ENUM | AvailableCommandsPacket::ARG_FLAG_VALID;
    }
}