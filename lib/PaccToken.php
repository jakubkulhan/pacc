<?php
/**
 * Represents one token
 */
class PaccToken
{
    const
        ID = 1,
        RULESTART = 2,
        RULEEND = 3,
        ALTER = 4,
        CODESTART = 5,
        CODE = 6,
        CODEEND = 7,
        STRING = 8;

    /**
     * @var int
     */
    public $type;

    /**
     * @var string
     */
    public $content;

    /**
     * @var int
     */
    public $line;

    /**
     * @var int
     */
    public $position;

    /**
     * Initializes instance
     * @param int
     * @param string
     * @param int
     * @param int
     */
    public function __construct($type, $content, $line, $position)
    {
        $this->type = $type;
        $this->content = $content;
        $this->line = $line;
        $this->position = $position;
    }
}
