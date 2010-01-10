<?php
/**
 * Represents jump from one state to another
 */
class PaccLRJump
{
    /**
     * Begining state
     * @var PaccSet<PaccLRItem>
     */
    public $from;

    /**
     * @var PaccSymbol
     */
    public $symbol;

    /**
     * Ending state
     * @var PaccSet<PaccLRItem>
     */
    public $to;

    /**
     * Initializes instance
     * @param PaccSet<PaccLRItem>
     * @param PaccSymbol
     * @param PaccSet<PaccLRItem>
     */
    public function __construct($from, $symbol, $to)
    {
        $this->from = $from;
        $this->symbol = $symbol;
        $this->to = $to;
    }
}
