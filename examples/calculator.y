<?php
class Calculator
{
    private $tokens;

    private function currentToken()
    {
        return current($this->tokens);
    }

    private function currentTokenType()
    {
        return NULL;
    }

    private function currentTokenContent()
    {
        return current($this->tokens);
    }

    private function nextToken()
    {
        return next($this->tokens);
    }

    public function calculate($expression)
    {
        $this->tokens = str_split(preg_replace('~[^0-9+()*/-]+~', '', $expression));
        reset($this->tokens);
        return $this->doParse();
    }
---

expression
    : component { $$ = $1; }
    | component '+' component { $$ = $1 + $3; }
    | component '-' component { $$ = $1 - $3; }
    |
    ;

factor
    : number { $$ = intval($1); }
    | '(' expression ')' { $$ = $2; }
    ;

component
    : factor { $$ = $1; }
    | factor '*' factor { $$ = $1 * $3; }
    | factor '/' factor { $$ = $1 / $3; }
    ;

digit : '0' | '1' | '2' | '3' | '4' | '5' | '6' | '7' | '8' | '9' ;

number
    : digit { $$ = $1; }
    | digit number { $$ = $1 . $2; }
    ;

---
}

$calculator = new Calculator;
echo $calculator->calculate(file_get_contents('php://stdin')) . "\n";
