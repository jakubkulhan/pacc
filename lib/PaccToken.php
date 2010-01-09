<?php
/**
 * Represents one token
 */
abstract class PaccToken
{
    /**
     * @var string
     */
    public $lexeme;

    /**
     * @var int
     */
    public $line;

    /**
     * @var int
     */
    public $position;

    /**
     * @var mixed
     */
    public $value;

    /**
     * Initializes instance
     * @param string
     * @param int
     * @param int
     */
    public function __construct($lexeme, $line, $position)
    {
        $this->lexeme = $lexeme;
        $this->line = $line;
        $this->position = $position;

        $this->value();
    }

    /**
     * Convert lexeme to value - constructor extension point
     */
    protected function value()
    {
        $this->value = $this->lexeme;
    }
}
