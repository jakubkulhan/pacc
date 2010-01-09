<?php
/**
 * Grammar production
 */
class PaccProduction
{
    /**
     * @var PaccNonterminal
     */
    public $left;

    /**
     * @var PaccSymbol[]
     */
    public $right;

    /**
     * @var int
     */
    public $index;

    /**
     * @var string
     */
    public $code;

    /**
     * Initializes production
     * @param PaccNonterminal
     * @param PaccSymbol[]
     * @param string
     */
    public function __construct(PaccNonterminal $left, array $right, $code = NULL)
    {
        $this->left = $left;

        foreach ($right as $symbol) {
            if (!($symbol instanceof PaccSymbol)) {
                throw new InvalidArgumentException('Right has to be array of PaccSymbol.');
            }
        }
        $this->right = $right;

        $this->code = $code;
    }

    /**
     * @return bool
     */
    public function __eq($o)
    {
        if ($o instanceof self &&
            $this->left->__eq($o->left) &&
            count($this->right) === count($o->right) &&
            $this->code === $o->code)
        {
            for ($i = 0, $len = count($this->right); $i < $len; ++$i) {
                if (!$this->right[$i]->__eq($o->right[$i])) { return FALSE; }
            }

            return TRUE;
        }

        return FALSE;
    }
}
