<?php
/**
 * One grammar symbol
 */
abstract class PaccSymbol
{
    /**
     * @var int
     */
    public $index;

    /**
     * @var string
     */
    public $name;

    /**
     * Initializes instance
     * @param string
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function __toString() { return $this->name; }

    /**
     * @return bool
     */
    public function __eq($o)
    {
        if ($o instanceof self && $o->name === $this->name) { return TRUE; }
        return FALSE;
    }
}
