<?php
/**
 * Converts token stream to set of rules
 */
class PaccParser
{
    /**
     * Lexer providing token stream
     * @var PaccLexer
     */
    private $lexer;

    /**
     * Set of rules
     * @var array
     */
    private $rules = array();

    /**
     * Initializes instance
     * @param PaccLexer
     */
    public function __construct(PaccLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * Parses token stream
     * @return array set of rules
     */
    public function parse()
    {
        $this->lexer->rewind();
        $this->rules();
        return $this->rules;
    }

    /**
     * @return void
     */
    private function rules()
    {
        if (!$this->lexer->valid()) { throw new PaccUnexpectedEnd(); }

        do {
            $rule = $this->rule();
            $this->rules[$rule->name->content] = $rule->exps;
        } while ($this->lexer->valid());
    }

    /**
     * @return stdClass
     */
    private function rule()
    {
        $name = $this->lexer->current();
        if ($name->type !== PaccToken::ID) { throw new PaccUnexpectedToken($name); }
        $this->lexer->next();

        if ($this->lexer->current()->type !== PaccToken::RULESTART) {
            throw new PaccUnexpectedToken($this->lexer->current());
        }
        $this->lexer->next();

        $expressions = array();
        do {
            $expressions[] = $this->expression();
        } while ($this->lexer->current()->type === PaccToken::ALTER && $this->lexer->next());

        if ($this->lexer->current()->type !== PaccToken::RULEEND) {
            throw new PaccUnexpectedToken($this->lexer->current());
        }
        $this->lexer->next();

        return (object) array('name' => $name, 'exps' => $expressions);
    }

    /**
     * @return array
     */
    private function expression()
    {
        $terms = $this->terms();

        $code = NULL;
        if ($this->lexer->current()->type === PaccToken::CODESTART) {
            $this->lexer->next();
            if ($this->lexer->current()->type !== PaccToken::CODE) {
                throw new PaccUnexpectedToken($this->lexer->current());
            }
            $code = $this->lexer->current();

            $this->lexer->next();
            if ($this->lexer->current()->type !== PaccToken::CODEEND) {
                throw new PaccUnexpectedToken($this->lexer->current());
            }
            $this->lexer->next();
        }

        return array($terms, $code);
    }

    /**
     * @return array
     */
    private function terms()
    {
        $terms = array();

        while (
            ($this->lexer->current()->type === PaccToken::ID ||
            $this->lexer->current()->type === PaccToken::STRING) && 
            $this->lexer->valid()
        ) {
            $terms[] = $this->lexer->current();
            $this->lexer->next();
        }

        return $terms;
    }
}
