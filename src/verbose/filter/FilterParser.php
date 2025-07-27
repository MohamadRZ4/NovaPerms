<?php

namespace MohamadRZ\NovaPerms\verbose\filter;

final class FilterParser {

    private string $expression;
    private int $position = 0;
    private int $length;

    public function parse(string $expression): VerboseFilter {
        $this->expression = trim($expression);
        $this->length = strlen($this->expression);
        $this->position = 0;

        return $this->parseOr();
    }

    private function parseOr(): VerboseFilter {
        $left = $this->parseAnd();

        while ($this->consumeOperator('|')) {
            $right = $this->parseAnd();
            $left = new CombinedFilter($left, FilterOperator::OR, $right);
        }

        return $left;
    }

    private function parseAnd(): VerboseFilter {
        $left = $this->parseNot();

        while ($this->consumeOperator('&')) {
            $right = $this->parseNot();
            $left = new CombinedFilter($left, FilterOperator::AND, $right);
        }

        return $left;
    }

    private function parseNot(): VerboseFilter {
        if ($this->consumeOperator('!')) {
            return new NotFilter($this->parsePrimary());
        }

        return $this->parsePrimary();
    }

    private function parsePrimary(): VerboseFilter {
        $this->skipWhitespace();

        if ($this->consumeChar('(')) {
            $filter = $this->parseOr();
            $this->expectChar(')');
            return $filter;
        }

        $token = $this->readToken();

        if (str_contains($token, '.')) {
            return new PermissionFilter($token);
        }

        return new PlayerFilter($token);
    }

    private function consumeOperator(string $operator): bool {
        $this->skipWhitespace();

        if ($this->position + strlen($operator) <= $this->length &&
            substr($this->expression, $this->position, strlen($operator)) === $operator) {
            $this->position += strlen($operator);
            return true;
        }

        return false;
    }

    private function consumeChar(string $char): bool {
        $this->skipWhitespace();

        if ($this->position < $this->length && $this->expression[$this->position] === $char) {
            $this->position++;
            return true;
        }

        return false;
    }

    private function expectChar(string $char): void {
        if (!$this->consumeChar($char)) {
            throw new \InvalidArgumentException("Expected '$char' at position {$this->position}");
        }
    }

    private function readToken(): string {
        $this->skipWhitespace();
        $start = $this->position;

        while ($this->position < $this->length &&
            !in_array($this->expression[$this->position], ['&', '|', '!', '(', ')', ' ', "\t", "\n"])) {
            $this->position++;
        }

        return substr($this->expression, $start, $this->position - $start);
    }

    private function skipWhitespace(): void {
        while ($this->position < $this->length &&
            in_array($this->expression[$this->position], [' ', "\t", "\n"])) {
            $this->position++;
        }
    }
}
