grammar PaccParser

option (
   eol          = "\n";
   indentation  = "    ";
   parse        = "doParse";
   algorithm    = "RD";
)

@header {
/**
 * Fills grammar from token stream
 */
}

@inner {

    const
        ID = 'PaccIdToken',
        STRING = 'PaccStringToken',
        CODE = 'PaccCodeToken';

    /**
     * Token stream
     * @var PaccTokenStream
     */
    private $stream;

    /**
     * @var PaccGrammar
     */
    private $grammar;

    /**
     * @var string
     */
    private $grammar_name;

    /**
     * @var array
     */
    private $grammar_options = array();

    /**
     * @var PaccSet<PaccNonterminal>
     */
    private $nonterminals;

    /**
     * @var PaccSet<PaccTerminal>
     */
    private $terminals;

    /**
     * @var PaccSet<PaccProduction>
     */
    private $productions;

    /**
     * Start symbol
     * @var PaccNonterminal
     */
    private $start;

    /**
     * Initializes instance
     * @param PaccTokenStream
     */
    public function __construct(PaccTokenStream $stream)
    {
        $this->stream = $stream;
        $this->terminals = new PaccSet('PaccTerminal');
        $this->nonterminals = new PaccSet('PaccNonterminal');
        $this->productions = new PaccSet('PaccProduction');
    }

    /**
     * Parse
     * @return PaccGrammar
     */
    public function parse()
    {
        if ($this->grammar === NULL) {
            $this->doParse();
            $this->grammar = new PaccGrammar($this->nonterminals, $this->terminals, $this->productions, $this->start);
            $this->grammar->name = $this->grammar_name;
            $this->grammar->options = $this->grammar_options;
        }

        return $this->grammar;
    }
}

@currentToken {
    return $this->stream->current();
}

@currentTokenType {
    return get_class($this->stream->current());
}

@currentTokenLexeme {
    return $this->stream->current()->lexeme;
}

@nextToken {
    return $this->stream->next();
}

syntax : toplevels rules ;

toplevels
    : toplevel
    | toplevel toplevels
    ;

optsem : ';' | ;

toplevel
    : 'grammar' backslash_separated_name optsem
      {
          $this->grammar_name = $2;
      }

    | 'option' options optsem
    | '@' period_separated_name '{' CODE '}' optsem
      {
          $this->grammar_options[$2] = $4->value;
      }
    ;

period_separated_name
    : ID '.' period_separated_name { $$ = $1->value . '.' . $3; }
    | ID { $$ = $1->value; }
    ;

backslash_separated_name
    : ID '\\' backslash_separated_name { $$ = $1->value . '\\' . $3; }
    | ID { $$ = $1->value; }
    ;

options
    : single_option
    | '(' more_options ')'
    ;

more_options
    : single_option
    | single_option ';'
    | single_option ';' more_options
    ;

single_option
    : period_separated_name '=' STRING
      {
          $this->grammar_options[$1] = $3->value;
      }

    | period_separated_name '=' '{' CODE '}'
      {
          $this->grammar->options[$1] = $4->value;
      }
    ;

rules 
    : rule
    | rule rules
    ;

rule
    : ID ':' expressions ';'
      {
          $name = new PaccNonterminal($1->value);
          if (($found = $this->nonterminals->find($name)) !== NULL) { $name = $found; }
          else { $this->nonterminals->add($name); }

          if ($this->start === NULL) {
              $this->start = $name;
          }

          foreach ($3 as $expression) {
              list($terms, $code) = $expression;
              $production = new PaccProduction($name, $terms, $code);
              if (($found = $this->productions->find($production)) === NULL) {
                  $this->productions->add($production);
              }
          }
      }
    ;

expressions 
    : expression { $$ = array($1); }
    | expression '|' expressions { $$ = array_merge(array($1), $3); }
    ;

expression 
    : terms_or_nothing { $$ = array($1, NULL); }
    | terms_or_nothing '{' CODE '}' { $$ = array($1, $3->value); }
    ;

terms_or_nothing
    : /* nothing */ { $$ = array(); }
    | terms { $$ = $1; }
    ;

terms 
    : term  { $$ = array($1); }
    | term terms { $$ = array_merge(array($1), $2); }
    ;

term 
    : ID 
      {
          if (ord($1->value[0]) >= 65 /* A */ && ord($1->value[0]) <= 90 /* Z */) { // terminal
              $term = new PaccTerminal($1->value, $1->value, NULL);
              if (($found = $this->terminals->find($term)) !== NULL) { $term = $found; }
              else { $this->terminals->add($term); }

          } else { // nonterminal
              $term = new PaccNonterminal($1->value);
              if (($found = $this->nonterminals->find($term)) !== NULL) { $term = $found; }
              else { $this->nonterminals->add($term); }
          }

          $$ = $term;
      }
    | STRING
      {
          $term = new PaccTerminal($1->value, NULL, $1->value);
          if (($found = $this->terminals->find($term)) !== NULL) { $term = $found; }
          else { $this->terminals->add($term); }

          $$ = $term;
      }
    ;
