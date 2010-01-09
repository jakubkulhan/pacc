grammar Calculator

@inner {
    private $tokens;

    public function calculate($expression)
    {
        $this->tokens = str_split(preg_replace('~[^0-9+()*/-]+~', '', $expression));
        reset($this->tokens);
        return $this->doParse();
    }
}

@currentToken {
    return current($this->tokens);
}

@currentTokenType {
    return NULL;
}

@currentTokenLexeme {
    return current($this->tokens);
}

@nextToken {
    return next($this->tokens);
}

@footer {
    $calculator = new Calculator;
    echo $calculator->calculate(file_get_contents('php://stdin')) . "\n";
}


expression
    : /* nothing */ { $$ = 0; }
    | component { $$ = $1; }
    | component '+' expression { $$ = $1 + $3; }
    ;

factor
    : number { $$ = intval($1); }
    | '(' expression ')' { $$ = $2; }
    ;

component
    : factor { $$ = $1; }
    | factor '*' factor { $$ = $1 * $3; }
    ;

number
    : digit { $$ = $1; }
    | digit number { $$ = $1 . $2; }
    ;

digit : '0' | '1' | '2' | '3' | '4' | '5' | '6' | '7' | '8' | '9' ;
