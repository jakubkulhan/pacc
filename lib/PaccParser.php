<?php
/**
 * Fills grammar from token stream
 */
class PaccParser
{
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
            for (;;) {
                if ($this->stream->current() instanceof PaccIdToken &&
                    $this->stream->current()->value === 'grammar')
                {
                    $this->stream->next();
                    $this->grammar_name = $this->backslashSeparatedName();

                } else if ($this->stream->current() instanceof PaccIdToken && 
                    $this->stream->current()->value === 'option')
                {
                    $this->stream->next();
                    $this->options();

                } else if ($this->stream->current() instanceof PaccSpecialToken && 
                    $this->stream->current()->value === '@')
                {
                    $this->stream->next();
                    $name = $this->periodSeparatedName();
                    $this->grammar_options[$name] = $this->code();

                } else { break; }

                // optional semicolon
                if ($this->stream->current() instanceof PaccSpecialToken &&
                    $this->stream->current()->value === ';')
                {
                    $this->stream->next();
                }
            }

            $this->rules();

            $this->grammar = new PaccGrammar($this->nonterminals, $this->terminals, $this->productions, $this->start);
            $this->grammar->name = $this->grammar_name;
            $this->grammar->options = $this->grammar_options;
        }

        return $this->grammar;
    }

    /**
     * @return string
     */
    private function backslashSeparatedName() { return $this->separatedName('\\'); }

    /**
     * @return string
     */
    private function periodSeparatedName() { return $this->separatedName('.'); }

    /**
     * @return string
     */
    private function separatedName($separator)
    {
        $name = '';
        $prev = NULL;

        while ((($this->stream->current() instanceof PaccSpecialToken &&
            $this->stream->current()->value === $separator) ||
            $this->stream->current() instanceof PaccIdToken) &&
            !($prev === NULL && $this->stream->current()->value === $separator) &&
            ($prev === NULL || get_class($this->stream->current()) !== get_class($prev)))
        {
            $name .= $this->stream->current()->value;
            $prev = $this->stream->current();
            $this->stream->next();
        }

        if (!($prev instanceof PaccIdToken)) {
            throw new PaccUnexpectedToken($this->stream->current());
        }

        return $name;
    }

    /**
     * @return string
     */
    private function code()
    {
        if (!($this->stream->current() instanceof PaccSpecialToken &&
            $this->stream->current()->value === '{'))
        {
            throw new PaccUnexpectedToken($this->stream->current());
        }
        $this->stream->next();

        if (!($this->stream->current() instanceof PaccCodeToken)) {
            throw new PaccUnexpectedToken($this->stream->current());
        }
        $code = $this->stream->current()->value;
        $this->stream->next();

        if (!($this->stream->current() instanceof PaccSpecialToken &&
            $this->stream->current()->value === '}'))
        {
            throw new PaccUnexpectedToken($this->stream->current());
        }
        $this->stream->next();

        return $code;
    }

    /**
     * @return void
     */
    private function options()
    {
        if (!($this->stream->current() instanceof PaccSpecialToken && 
            $this->stream->current()->value === '('))
        {
            return $this->singleOption();
        }
        $this->stream->next();

        for (;;) {
            $this->singleOption();
            if ($this->stream->current() instanceof PaccSpecialToken) {
                if ($this->stream->current()->value === ')') {
                    $this->stream->next();
                    break;

                } else if ($this->stream->current()->value === ';') {
                    $this->stream->next();
                    if ($this->stream->current() instanceof PaccSpecialToken &&
                        $this->stream->current()->value === ')')
                    {
                        $this->stream->next();
                        break;
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    private function singleOption()
    {
        $name = $this->periodSeparatedName();
        $value = NULL;

        if (!($this->stream->current() instanceof PaccSpecialToken &&
            $this->stream->current()->value === '='))
        {
            throw new PaccUnexpectedToken($this->stream->current());
        }
        $this->stream->next();

        if ($this->stream->current() instanceof PaccStringToken) {
            $value = $this->stream->current()->value;
            $this->stream->next();

        } else if ($this->stream->current() instanceof PaccSpecialToken &&
            $this->stream->current()->value === '{')
        {
            $value = $this->code();

        } else {
            throw new PaccUnexpectedToken($this->stream->current());
        }

        $this->grammar_options[$name] = $value;
    }

    /**
     * @return void
     */
    private function rules()
    {
        do {
            if (!($this->stream->current() instanceof PaccIdToken)) {
                throw new PaccUnexpectedToken($this->stream->current());
            }

            $name = new PaccNonterminal($this->stream->current()->value);
            if (($found = $this->nonterminals->find($name)) !== NULL) { $name = $found; }
            else { $this->nonterminals->add($name); }
            $this->stream->next();

            if ($this->start === NULL) {
                $this->start = $name;
            }

            if (!($this->stream->current() instanceof PaccSpecialToken &&
                $this->stream->current()->value === ':'))
            {
                throw new PaccUnexpectedToken($this->stream->current());
            }
            $this->stream->next();

            do {
                list($terms, $code) = $this->expression();
                $production = new PaccProduction($name, $terms, $code);
                if (($found = $this->productions->find($production)) === NULL) {
                    $this->productions->add($production);
                }

            } while ($this->stream->current() instanceof PaccSpecialToken &&
                $this->stream->current()->value === '|' &&
                !($this->stream->next() instanceof PaccEndToken));

            if (!($this->stream->current() instanceof PaccSpecialToken &&
                $this->stream->current()->value === ';'))
            {
                throw new PaccUnexpectedToken($this->stream->current());
            }
            $this->stream->next();

        } while (!($this->stream->current() instanceof PaccEndToken));
    }

    /**
     * @return array
     */
    private function expression()
    {
        $terms = $this->terms();

        $code = NULL;
        if ($this->stream->current() instanceof PaccSpecialToken &&
            $this->stream->current()->value === '{')
        {
            $code = $this->code();
        }

        return array($terms, $code);
    }

    /**
     * @return array
     */
    private function terms()
    {
        $terms = array();

        while (($this->stream->current() instanceof PaccIdToken ||
            $this->stream->current() instanceof PaccStringToken))
        {
            $t = $this->stream->current();
            $this->stream->next();

            if ($t instanceof PaccIdToken) {
                if (ord($t->value[0]) >= 65 /* A */ && ord($t->value[0]) <= 90 /* Z */) { // terminal
                    $term = new PaccTerminal($t->value, $t->value, NULL);
                    if (($found = $this->terminals->find($term)) !== NULL) { $term = $found; }
                    else { $this->terminals->add($term); }

                } else { // nonterminal
                    $term = new PaccNonterminal($t->value);
                    if (($found = $this->nonterminals->find($term)) !== NULL) { $term = $found; }
                    else { $this->nonterminals->add($term); }
                }

            } else {
                assert($t instanceof PaccStringToken);
                $term = new PaccTerminal($t->value, NULL, $t->value);
                if (($found = $this->terminals->find($term)) !== NULL) { $term = $found; }
                else { $this->terminals->add($term); }
            }

            $terms[] = $term;
        }

        return $terms;
    }
}
