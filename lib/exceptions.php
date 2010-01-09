<?php
/**
 * Thrown if there is some unexpected token in stream
 */
class PaccUnexpectedToken extends Exception
{
    /**
     * @var PaccToken
     */
    public $token;

    public function __construct(PaccToken $t, Exception $previous = NULL)
    {
        $this->token = $t;
        parent::__construct(
            'Unexcepted token `' . $t->lexeme .
            '` of type ' . get_class($t) . 
            ' on line ' . $t->line . 
            ' at position ' . $t->position .
            '.',
            0,
            $previous
        );
    }
}

/**
 * Thrown when token stream unexpectedly ended
 */
class PaccUnexpectedEnd extends Exception
{
    public function __construct(Exception $previous = NULL)
    {
        parent::__construct(
            'Unexcepted end.',
            0,
            $previous
        );
    }
}

/**
 * Thrown if there is something bad with some identifier (e.g. bad caps)
 */
class PaccBadIdentifier extends Exception
{
    /**
     * @var PaccToken
     */
    public $token;

    public function __construct(PaccToken $t, Exception $previous = NULL)
    {
        $this->token = $t;
        parent::__construct(
            'Bad identifier `' . $t->value . 
            '` on line ' . $t->line . 
            ' at position ' . $t->position .
            '.',
            0,
            $previous
        );
    }
}
