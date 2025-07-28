<?php

namespace MohamadRZ\NovaPerms\context\providers;

use MohamadRZ\NovaPerms\context\ContextSet;
use MohamadRZ\NovaPerms\context\MutableContextSet;

class DefaultContextProvider
{
    private array $defaultContexts = [];
    private string $configPath;

    public function __construct(?string $configPath = null)
    {
        $this->configPath = $configPath ?? __DIR__ . '/contexts.json';
        $this->loadDefaultContexts();
    }

    public function getDefaultContexts(): ContextSet
    {
        $contextSet = MutableContextSet::create();

        foreach ($this->defaultContexts as $key => $values) {
            foreach ($values as $value) {
                $contextSet->add($key, $value);
            }
        }

        return $contextSet->immutableCopy();
    }

    public function applyDefaultContexts(ContextSet $providedContexts): ContextSet
    {
        return $providedContexts->isEmpty() ? $this->getDefaultContexts() : $providedContexts;
    }

    public function mergeWithDefaults(ContextSet $providedContexts): ContextSet
    {
        $merged = MutableContextSet::create();

        foreach ($this->defaultContexts as $key => $values) {
            foreach ($values as $value) {
                $merged->add($key, $value);
            }
        }

        foreach ($providedContexts->getContexts() as $context) {
            $merged->removeAll($context->getKey());
            $merged->addContext($context);
        }

        return $merged->immutableCopy();
    }

    public function reload(): void
    {
        $this->loadDefaultContexts();
    }

    private function loadDefaultContexts(): void
    {
        $this->defaultContexts = [];

        if (!file_exists($this->configPath)) {
            return;
        }

        $content = file_get_contents($this->configPath);
        $data = json_decode($content, true);

        if (!is_array($data) || !isset($data['default-contexts'])) {
            return;
        }

        $defaultContextsData = $data['default-contexts'];
        if (is_array($defaultContextsData)) {
            foreach ($defaultContextsData as $key => $value) {
                $key = strtolower(trim($key));
                if (empty($key)) continue;

                if (is_array($value)) {
                    foreach ($value as $val) {
                        $val = trim($val);
                        if (!empty($val)) {
                            if (!isset($this->defaultContexts[$key])) {
                                $this->defaultContexts[$key] = [];
                            }
                            $this->defaultContexts[$key][] = $val;
                        }
                    }
                } else {
                    $value = trim($value);
                    if (!empty($value)) {
                        $this->defaultContexts[$key] = [$value];
                    }
                }
            }
        }
    }
}
