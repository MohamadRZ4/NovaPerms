<?php

namespace MohamadRZ\NovaPerms\commands\group;

use CortexPE\Commando\args\BaseArgument;
use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\storage\DataConstraints;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;

class CreateGroup extends BaseSubCommand
{

    /**
     * @return void
     * @throws ArgumentOrderException
     */
    protected function prepare(): void {
        $this->registerArgument(0, new RawStringArgument("name"));
        $this->registerArgument(1, new IntegerArgument("weight", true));
        $this->registerArgument(2, new RawStringArgument("displayname", true));
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     */
    #[\Override] public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $groupManager = NovaPermsPlugin::getGroupManager();

        $name = $args["name"];
        if (!DataConstraints::groupNameTest($name)) {
            $sender->sendMessage(NovaPermsPlugin::PREFIX . " §4$name §cis not a valid group name.");
            return;
        }



        $weight = $args["weight"] ?? -1;
        $displayName = $args["displayname"] ?? "";

        $result = $groupManager->createGroup($name, $weight, $displayName);
        if ($result) {
            $sender->sendMessage(NovaPermsPlugin::PREFIX . " §b$name §awas successfully created.");
        } else {
            $sender->sendMessage(NovaPermsPlugin::PREFIX . " §4$name §calready exist!.");
        }

    }

    protected function sendUsage(): void
    {
        $descriptions = [
            0 => "The name of the group",
            1 => "The weight of the group",
            2 => "The display name of the group"
        ];
        $usage  = NovaPermsPlugin::PREFIX." §3§lCommand Usage §r§3- §b{$this->getName()}\n";
        $usage .= NovaPermsPlugin::PREFIX." §b> §7" . $this->getDescription() . "\n";
        $usage .= NovaPermsPlugin::PREFIX." §3Arguments:\n";

        foreach($this->getArgumentList() as $index => $argSet) {
            foreach($argSet as $argument) {
                /** @var BaseArgument $argument */
                $desc = $descriptions[$index] ?? "No description";
                $brackets = $argument->isOptional() ? ["[", "]"] : ["<", ">"];
                $usage .= NovaPermsPlugin::PREFIX." §b- §8{$brackets[0]}§7{$argument->getName()}§8{$brackets[1]} §3-> §7{$desc}\n";
            }
        }

        $this->currentSender->sendMessage($usage);
    }

    public function sendError(int $errorCode, array $args = []): void {
        $descriptions = [
            0 => "The name of the group",
            1 => "The weight of the group",
            2 => "The display name of the group"
        ];
        $usage  = NovaPermsPlugin::PREFIX." §3§lCommand Usage §r§3- §b{$this->getName()}\n";
        $usage .= NovaPermsPlugin::PREFIX." §b> §7" . $this->getDescription() . "\n";
        $usage .= NovaPermsPlugin::PREFIX." §3Arguments:\n";

        foreach($this->getArgumentList() as $index => $argSet) {
            foreach($argSet as $argument) {
                /** @var BaseArgument $argument */
                $desc = $descriptions[$index] ?? "No description";
                $brackets = $argument->isOptional() ? ["[", "]"] : ["<", ">"];
                $usage .= NovaPermsPlugin::PREFIX." §b- §8{$brackets[0]}§7{$argument->getName()}§8{$brackets[1]} §3-> §7{$desc}\n";
            }
        }

        $this->currentSender->sendMessage($usage);
    }
}