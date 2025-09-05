<?php

namespace MohamadRZ\NovaPerms\utils;

use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;

class CommandTreeBuilder {

    private string $root;
    private array $tree = [];

    private function __construct(string $root) {
        $this->root = $root;
    }

    public static function create(string $root) : self {
        return new self($root);
    }

    public function branch(array $path, array $params = []) : self {
        $this->tree[] = [$path, $params];
        return $this;
    }

    public function build(AvailableCommandsPacket $packet) : AvailableCommandsPacket {
        $overloads = [];

        foreach ($this->tree as [$path, $params]) {
            $paramObjects = [];

            foreach ($path as $step) {
                $paramObjects[] = CommandParameter::enum($step, new CommandEnum($step, [$step]), CommandParameter::FLAG_FORCE_COLLAPSE_ENUM);
            }

            foreach ($params as $p) {
                [$type, $name, $optional] = $p;
                $paramObjects[] = CommandParameter::standard($name, $type, 0, $optional);
            }

            $overloads[] = new CommandOverload(false, $paramObjects);
        }

        if (isset($packet->commandData[$this->root])) {
            $packet->commandData[$this->root]->overloads = $overloads;
        }
        return $packet;
    }
}