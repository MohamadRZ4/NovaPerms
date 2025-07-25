<?php

namespace MohamadRZ\StellarRanks\verbose\filter;

enum FilterOperator: string {
    case AND = 'and';
    case OR = 'or';
    case NOT = 'not';
}
