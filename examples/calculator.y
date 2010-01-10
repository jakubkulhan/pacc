grammar Calculator

option (
    eol         = "\n";
    indentation = "    ";
    parse       = "doParse";
    algorithm   = "LR";
)

@inner {
    const NUMBER = 1;

    private $expression;
    private $token;

    public function calculate($expression)
    {
        $this->expression = $expression;
        $this->_nextToken();
        return $this->doParse();
    }
}

@currentToken {
    return $this->token;
}

@currentTokenType {
    if (preg_match('~^[0-9]+$~', $this->token)) {
        return self::NUMBER;
    }
    return NULL;
}

@currentTokenLexeme {
    return $this->token;
}

@nextToken {
    if (!preg_match('~^([0-9]+|\(|\)|\+|-|\*|/)~', $this->expression, $m)) {
        $this->expression = NULL;
        $this->token = NULL;
        return;
    }

    $this->token = $m[1];
    $this->expression = substr($this->expression, strlen($m[1]));
}

@footer {
    $calculator = new Calculator;
    echo $calculator->calculate(file_get_contents('php://stdin')) . "\n";
}


expression
    : /* nothing */ { $$ = 0; }
    | component { $$ = $1; }
    | expression '+' component { $$ = $1 + $3; }
    | expression '-' component { $$ = $1 - $3; }
    ;

factor
    : NUMBER { $$ = intval($1); }
    | '(' expression ')' { $$ = $2; }
    ;

component
    : factor { $$ = $1; }
    | component '*' factor { $$ = $1 * $3; }
    | component '/' factor { $$ = $1 / $3; }
    ;
