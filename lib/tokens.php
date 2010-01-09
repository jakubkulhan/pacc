<?php
/**
 * Identifier token
 */
class PaccIdToken extends PaccToken {}

/**
 * String token
 */
class PaccStringToken extends PaccToken
{
    protected function value()
    {
        // FIXME: eval is evil!
        if ($this->lexeme[0] === '"' || $this->lexeme[0] === "'") {
            $this->value = eval('return ' . $this->lexeme . ';');
        } else {
            $this->value = substr($this->lexeme, 1, strlen($this->lexeme) - 2);
        }
    }
}

/**
 * Special character token
 */
class PaccSpecialToken extends PaccToken {}

/**
 * Code token
 */
class PaccCodeToken extends PaccToken {}

/**
 * Whitespace token
 */
class PaccWhitespaceToken extends PaccToken {}

/**
 * Comment token
 */
class PaccCommentToken extends PaccToken {}

/**
 * End token
 */
class PaccEndToken extends PaccToken {}

/**
 * Bad token
 */
class PaccBadToken extends PaccToken {}
