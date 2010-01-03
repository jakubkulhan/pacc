<?php
class PaccParser
{

    const ID = PaccToken::ID,
          CODE = PaccToken::CODE,
          STRING = PaccToken::STRING;

    private $lexer;

    public function __construct(PaccLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    private function currentToken()
    {
        if (!$this->lexer->valid()) { return NULL; }
        return $this->lexer->current();
    }

    private function currentTokenType()
    {
        if (!$this->lexer->valid()) { return NULL; }
        return $this->lexer->current()->type;
    }

    private function currentTokenContent()
    {
        if (!$this->lexer->valid()) { return NULL; }
        return $this->lexer->current()->content;
    }

    private function nextToken()
    {
        return $this->lexer->next();
    }

    public function parse()
    {
        $this->lexer->rewind();
        return $this->doParse();
    }
---

rules 
    : rule                          { $$ = array($1->name => $1->exps); }
    | rule rules                    { $$ = array($1->name => $1->exps); $$ = array_merge($$, $2); }
    ;

rule
    : ID ':' expressions ';'        { $$ = (object) array('name' => $1->content, 'exps' => $3); }
    ;

expressions 
    : expression                    { $$ = array($1); }
    | expression '|' expressions    { $$ = array_merge(array($1), $3); }
    ;

expression 
    : terms                         { $$ = array($1, NULL); }
    | terms '{' CODE '}'            { $$ = array($1, $3); }
    ;

terms 
    : term                          { $$ = array($1); }
    | term terms                    { $$ = array_merge(array($1), $2); }
    |                               { $$ = array(); }
    ;

term 
    : ID                            { $$ = $1; }
    | STRING                        { $$ = $1; }
    ;

---
}
