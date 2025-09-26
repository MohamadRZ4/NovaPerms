<?php

namespace MohamadRZ\NovaPerms\commands\group;

use MohamadRZ\CommandLib\BaseCommand;
use MohamadRZ\NovaPerms\commands\args\GroupArgument;
use MohamadRZ\NovaPerms\NovaPermsPlugin;
use MohamadRZ\NovaPerms\storage\DataConstraints;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;

class DeleteGroup extends BaseCommand
{

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = [])
    {
        $this->setPermission("novaperms.use");
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    /**
     * @return void
     */
    #[\Override] public function setup(): void
    {
        $this->addArgument(new GroupArgument("group"));
    }

    protected function onRun(CommandSender $sender, array $args, string $rootLabel): void
    {
        $groupManager = NovaPermsPlugin::getGroupManager();

        $name = $args["group"];
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

/*    protected function sendUsage(): void
    {
        $descriptions = [
            0 => "The name of the group"
        ];
        $usage  = NovaPermsPlugin::PREFIX." §3§lCommand Usage §r§3- §b{$this->getName()}\n";
        $usage .= NovaPermsPlugin::PREFIX." §b> §7" . $this->getDescription() . "\n";
        $usage .= NovaPermsPlugin::PREFIX." §3Arguments:\n";

        foreach($this->getArgumentList() as $index => $argSet) {
            foreach($argSet as $argument) {
                * @var BaseArgument $argument
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
        ];
        $usage  = NovaPermsPlugin::PREFIX." §3§lCommand Usage §r§3- §b{$this->getName()}\n";
        $usage .= NovaPermsPlugin::PREFIX." §b> §7" . $this->getDescription() . "\n";
        $usage .= NovaPermsPlugin::PREFIX." §3Arguments:\n";

        foreach($this->getArgumentList() as $index => $argSet) {
            foreach($argSet as $argument) {
                * @var BaseArgument $argument
                $desc = $descriptions[$index] ?? "No description";
                $brackets = $argument->isOptional() ? ["[", "]"] : ["<", ">"];
                $usage .= NovaPermsPlugin::PREFIX." §b- §8{$brackets[0]}§7{$argument->getName()}§8{$brackets[1]} §3-> §7{$desc}\n";
            }
        }

        $this->currentSender->sendMessage($usage);
    }*/
}