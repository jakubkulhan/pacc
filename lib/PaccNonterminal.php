<?php
/**
 * Nonterminal symbol
 */
class PaccNonterminal extends PaccSymbol {
    public function __toString()
    {
        return $this->name;
    }
}
