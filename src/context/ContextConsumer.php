<?php

namespace MohamadRZ\NovaPerms\context;

interface ContextConsumer {

    /**
     * @param string $key
     * @param string $value
     */
    public function accept(string $key, string $value): void;
}
