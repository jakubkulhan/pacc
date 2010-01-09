<?php
/**
 * Terminal symbol
 */
class PaccTerminal extends PaccSymbol
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $value;

    /**
     * Inizializes instance
     * @param string
     * @param string
     * @param string
     */
    public function __construct($name, $type = NULL, $value = NULL)
    {
        parent::__construct($name);
        $this->type = $type;
        $this->value = $value;
    }
}
