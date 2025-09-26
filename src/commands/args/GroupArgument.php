<?php

namespace MohamadRZ\NovaPerms\commands\args;

use MohamadRZ\CommandLib\CommandArgument;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;

class GroupArgument implements CommandArgument {
    private string $name;
    private bool $optional;
    private ?string $description = null;

    public function __construct(?string $name = null, bool $optional = false, ?string $description = null) {
        $this->name = $name ?? "group";
        $this->optional = $optional;
        $this->description = $description;
    }

    public function getName(): string {
        return $this->name;
    }

    public function isOptional(): bool {
        return $this->optional;
    }

    public function toParameter(): CommandParameter {
        $groupNames = [];
        foreach (NovaPermsPlugin::getGroupManager()->getAllGroups() as $group) {
            $groupNames[] = $group->getName();
        }

        $enum = new CommandEnum($this->name, $groupNames);

        return CommandParameter::enum(
            $this->name,
            $enum,
            CommandParameter::FLAG_FORCE_COLLAPSE_ENUM,
            $this->optional
        );
    }

    /**
     * @return string|null
     */
    #[\Override] public function getDescription(): ?string
    {
        return $this->description;
    }
}
