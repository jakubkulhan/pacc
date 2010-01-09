<?php
/**
 * Represents grammar
 */
class PaccGrammar
{
    /**
     * Grammar name
     * @var string
     */
    public $name;

    /**
     * Options
     * @var array
     */
    public $options = array();

    /**
     * @var PaccSet<PaccNonterminal>
     */
    public $nonterminals;

    /**
     * @var PaccSet<PaccTerminal>
     */
    public $terminals;

    /**
     * @var PaccSet<PaccProduction>
     */
    public $productions;

    /**
     * @var PaccNonterminal
     */
    public $start;

    /**
     * Initializes grammar G = (N, T, P, S)
     * @param PaccSet<PaccNonterminal>
     * @param PaccSet<PaccTerminal>
     * @param PaccSet<PaccProduction>
     * @param PaccNonterminal
     */
    public function __construct(PaccSet $nonterminals, PaccSet $terminals, PaccSet $productions, PaccNonterminal $start)
    {
        // check
        if ($nonterminals->getType() !== 'PaccNonterminal') {
            throw new InvalidArgumentException(
                'PaccSet<PaccNonterminal> expected, PaccSet<' . 
                $nonterminals->getType() . '> given.'
            );
        }

        if ($terminals->getType() !== 'PaccTerminal') {
            throw new InvalidArgumentException(
                'PaccSet<PaccTerminal> expected, PaccSet<' .
                $terminals->getType() . '> given.'
            );
        }

        if ($productions->getType() !== 'PaccProduction') {
            throw new InvalidArgumentException(
                'PaccSet<PaccProduction> expected, PaccSet<' .
                $productions->getType() . '> given.'
            );
        }

        // initialize
        $this->nonterminals = $nonterminals;
        $this->terminals = $terminals;
        $this->productions = $productions;
        $this->start = $start;
    }
}
