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

    /**
     * @return bool
     */
    public function __eq($o)
    {
        if ($o instanceof self && $o->name === $this->name &&
            $o->type === $this->type && $o->value === $this->value)
        {
            return TRUE;
        }

        return FALSE;
    }

    public function __toString()
    {
        return '`' . $this->name . '`';
    }
}
