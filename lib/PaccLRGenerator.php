<?php
/**
 * Generates LR parser
 */
class PaccLRGenerator extends PaccGenerator
{
    /**
     * @var PaccGrammar
     */
    private $grammar;

    /**
     * Max symbol index (for table pitch)
     * @var int
     */
    private $table_pitch;

    /**
     * @var PaccSet<PaccLRItem>[]
     */
    private $states;

    /**
     * @var PaccLRJump[]
     */
    private $jumps;

    /**
     * @var int[]
     */
    private $table = array();

    /**
     * @var string
     */
    private $generated;

    /**
     * Header of generated file
     * @var string
     */
    private $header;

    /**
     * Code to put inside the generated class
     * @var string
     */
    private $inner;

    /**
     * Footer of generated file
     * @var string
     */
    private $footer;

    /**
     * One indentation level
     * @var string
     */
    private $indentation = '    ';

    /**
     * End of line
     * @var string
     */
    private $eol = PHP_EOL;

    /**
     * Prefix for terminals
     * @var string
     */
    private $terminals_prefix = 'self::';

    /**
     * Name of parse method
     * @var string
     */
    private $parse = 'doParse';


    /**
     * Initializes generator
     * @param PaccGrammar
     */
    public function __construct(PaccGrammar $grammar)
    {
        $this->grammar = $grammar;

        // order sensitive actions!
        file_put_contents('php://stderr', 'augment... ');
        $this->augment();
        file_put_contents('php://stderr', 'indexes... ');
        $this->computeIndexes();
        file_put_contents('php://stderr', 'first... ');
        $this->computeFirst();
        file_put_contents('php://stderr', 'follow... ');
        $this->computeFollow();
        file_put_contents('php://stderr', 'states... ');
        $this->computeStates();
        file_put_contents('php://stderr', 'table... ');
        $this->computeTable();
        file_put_contents('php://stderr', "\n");

        foreach (array('header', 'inner', 'footer', 'indentation', 'eol', 'terminals_prefix', 'parse') as $name) {
            if (isset($grammar->options[$name])) {
                $this->$name = $grammar->options[$name];
            }
        }
    }

    /**
     * Generate parser
     * @return string
     */
    protected function generate()
    {
        if ($this->generated === NULL) { $this->doGenerate(); }

        return $this->generated;
    }

    /**
     * Really generates parser
     * @return string
     */
    private function doGenerate()
    {
        // header
        $this->generated .= '<?php' . $this->eol;

        if (strpos($this->grammar->name, '\\') === FALSE) { $classname = $this->grammar->name; }
        else {
            $namespace = explode('\\', $this->grammar->name);
            $classname = array_pop($namespace);
            $this->generated .= 'namespace ' . implode('\\', $namespace) . ';' . $this->eol;
        }

        $this->generated .= $this->header . $this->eol;
        $this->generated .= 'class ' . $classname . $this->eol . '{' . $this->eol;

        // parser
        $table = array();
        foreach ($this->table as $k => $v) {
            if ($v === NULL) { continue; }
            $table[] = $k . '=>' . $v;
        }
        $this->generated .= $this->indentation . 'private $_table = array(' . implode(',', $table) . ');' . $this->eol;
        $this->generated .= $this->indentation . 'private $_table_pitch = ' . $this->table_pitch . ';' . $this->eol;

        $terminals_types = array();
        $terminals_values = array();
        foreach ($this->grammar->terminals as $terminal) {
            if ($terminal->type !== NULL) {
                $terminals_types[] = $this->terminals_prefix . $terminal->type . '=>' . $terminal->index;
            } else if ($terminal->value !== NULL) {
                $terminals_values[] = var_export($terminal->value, TRUE) . '=>' . $terminal->index;
            }

        }
        $this->generated .= $this->indentation . 'private $_terminals_types = array(' . implode(',', $terminals_types) . ');' . $this->eol;
        $this->generated .= $this->indentation . 'private $_terminals_values = array(' . implode(',', $terminals_values) . ');' . $this->eol;

        $productions_lengths = array();
        $productions_lefts = array();
        foreach ($this->grammar->productions as $production) {
            $productions_lengths[] = $production->index . '=>' . count($production->right);
            $productions_lefts[] = $production->index . '=>' . $production->left->index;

            $this->generated .= $this->indentation . 'private function _reduce' . $production->index . '() {' . $this->eol;
            $this->generated .= $this->indentation . $this->indentation . 'extract(func_get_arg(0), EXTR_PREFIX_INVALID, \'_\');' . $this->eol;
            $this->generated .= $this->indentation . $this->indentation . $this->phpizeVariables('$$ = NULL;') . $this->eol;

            if ($production->code !== NULL) {
                $this->generated .= $this->indentation . $this->indentation . $this->phpizeVariables($production->code) . $this->eol;
            } else {
                $this->generated .= $this->indentation . $this->indentation . $this->phpizeVariables('$$ = $1;') . $this->eol;
            }

            $this->generated .= $this->indentation . $this->indentation . $this->phpizeVariables('return $$;') . $this->eol;
            $this->generated .= $this->indentation . '}' . $this->eol;
        }
        $this->generated .= $this->indentation . 'private $_productions_lengths = array(' . implode(',', $productions_lengths) . ');' . $this->eol;
        $this->generated .= $this->indentation . 'private $_productions_lefts = array(' . implode(',', $productions_lefts) . ');' . $this->eol;

        $this->generated .= <<<E
    private function {$this->parse}() {
        \$stack = array(NULL, 0);
        for (;;) {
            \$state = end(\$stack);
            \$terminal = 0;
            if (isset(\$this->_terminals_types[\$this->_currentTokenType()])) {
                \$terminal = \$this->_terminals_types[\$this->_currentTokenType()];
            } else if (isset(\$this->_terminals_values[\$this->_currentTokenLexeme()])) {
                \$terminal = \$this->_terminals_values[\$this->_currentTokenLexeme()];
            }

            if (!isset(\$this->_table[\$state * \$this->_table_pitch + \$terminal])) {
                throw new Exception('Illegal action.');
            }

            \$action = \$this->_table[\$state * \$this->_table_pitch + \$terminal];

            if (\$action === 0) { // => accept
                array_pop(\$stack); // go away, state!
                return array_pop(\$stack);

            } else if (\$action > 0) { // => shift
                array_push(\$stack, \$this->_currentToken());
                array_push(\$stack, \$action);
                \$this->_nextToken();

            } else { // \$action < 0 => reduce
                \$popped = array_splice(\$stack, count(\$stack) - (\$this->_productions_lengths[-\$action] * 2));
                \$args = array();
                if (\$this->_productions_lengths[-\$action] > 0) { 
                    foreach (range(0, (\$this->_productions_lengths[-\$action] - 1) * 2, 2) as \$i) {
                        \$args[\$i / 2 + 1] = \$popped[\$i];
                    }
                }

                \$goto = \$this->_table[end(\$stack) * \$this->_table_pitch + \$this->_productions_lefts[-\$action]];

                \$reduce = '_reduce' . (-\$action);
                if (method_exists(\$this, \$reduce)) {
                    array_push(\$stack, \$this->\$reduce(\$args));
                } else {
                    array_push(\$stack, NULL);
                }

                array_push(\$stack, \$goto);
            }
        }
    }


E;


        // footer
        foreach (array('currentToken', 'currentTokenType', 'currentTokenLexeme', 'nextToken') as $method) {
            if (isset($this->grammar->options[$method])) {
                $this->generated .= $indentation . 'private function _' . $method . '() {' . $this->eol;
                $this->generated .= $this->grammar->options[$method] . $this->eol;
                $this->generated .= $indentation . '}' . $this->eol . $this->eol;
            }
        }

        $this->generated .= $this->inner . $this->eol;
        $this->generated .= '}' . $this->eol;
        $this->generated .= $this->footer;
    }

    /**
     * Converts special variables to PHP variables
     * @param string
     * @return string
     */
    protected function phpizeVariables($s)
    {
        return str_replace('$$', '$__0', preg_replace('~\$(\d+)~', '$__$1', $s));
    }


    /**
     * Adds new start nonterminal and end terminal
     * @return void
     */
    private function augment()
    {
        $newStart = new PaccNonterminal('$start');
        $this->grammar->startProduction = new PaccProduction($newStart, array($this->grammar->start), NULL);
        $this->grammar->productions->add($this->grammar->startProduction);
        $this->grammar->nonterminals->add($newStart);
        $this->grammar->start = $newStart;

        $this->grammar->epsilon = new PaccTerminal('$epsilon');
        $this->grammar->epsilon->index = -1;

        $this->grammar->end = new PaccTerminal('$end');
        $this->grammar->end->index = 0;
        $this->grammar->end->first = new PaccSet('integer');
        $this->grammar->end->first->add($this->grammar->end->index);
    }

    /**
     * Compute grammar symbols and productions indexes
     * @return void
     */
    private function computeIndexes()
    {
        $i = 1;
        foreach ($this->grammar->terminals as $terminal) {
            $terminal->index = $i++;
            $terminal->first = new PaccSet('integer');
            $terminal->first->add($terminal->index);
        }
        $this->grammar->terminals->add($this->grammar->end);

        $this->max_terminal = $i - 1;

        foreach ($this->grammar->nonterminals as $nonterminal) {
            $nonterminal->first = new PaccSet('integer');
            $nonterminal->follow = new PaccSet('integer');
            $nonterminal->index = $i++;
        }

        $this->table_pitch = $i - 1;

        $i = 1;
        foreach ($this->grammar->productions as $production) {
            $production->index = $i++;
        }
    }

    /**
     * @return void
     */
    private function computeFirst()
    {
        foreach ($this->grammar->productions as $production) {
            if (count($production->right) === 0) {
                $production->left->first->add($this->grammar->epsilon->index);
            }
        }

        do {
            $done = TRUE;
            foreach ($this->grammar->productions as $production) {
                foreach ($production->right as $symbol) {
                    foreach ($symbol->first as $index) {
                        if ($index !== $this->grammar->epsilon->index &&
                            !$production->left->first->contains($index))
                        {
                            $production->left->first->add($index);
                            $done = FALSE;
                        }
                    }

                    if (!$symbol->first->contains($this->grammar->epsilon->index)) { break; }
                }
            }
        } while (!$done);
    }

    /**
     * @return void
     */
    private function computeFollow()
    {
        $this->grammar->start->follow->add($this->grammar->end->index);

        foreach ($this->grammar->productions as $production) {
            for ($i = 0, $len = count($production->right) - 1; $i < $len; ++$i) {
                if ($production->right[$i] instanceof PaccTerminal) { continue; }
                foreach ($production->right[$i + 1]->first as $index) {
                    if ($index === $this->grammar->epsilon->index) { continue; }
                    $production->right[$i]->follow->add($index);
                }
            }
        }

        do {
            $done = TRUE;
            foreach ($this->grammar->productions as $production) {
                for ($i = 0, $len = count($production->right); $i < $len; ++$i) {
                    if ($production->right[$i] instanceof PaccTerminal) { continue; }

                    $empty_after = TRUE;
                    for ($j = $i + 1; $j < $len; ++$j) {
                        if (!$production->right[$j]->first->contains($this->grammar->epsilon->index)) {
                            $empty_after = FALSE;
                            break;
                        }
                    }

                    if ($empty_after && !$production->right[$i]->follow->contains($production->left->follow)) {
                        $production->right[$i]->follow->add($production->left->follow);
                        $done = FALSE;
                    }
                }
            }
        } while (!$done);
    }

    /**
     * @return void
     */
    private function computeStates()
    {
        $items = new PaccSet('PaccLRItem');
        $items->add(new PaccLRItem($this->grammar->startProduction, 0, $this->grammar->end->index));
        $this->states = array($this->closure($items));
        $symbols = new PaccSet('PaccSymbol');
        $symbols->add($this->grammar->nonterminals);
        $symbols->add($this->grammar->terminals);

        for ($i = 0; $i < count($this->states); ++$i) { // intentionally count() in second clause
            foreach ($symbols as $symbol) {
                $jump = $this->jump($this->states[$i], $symbol);
                if ($jump->isEmpty()) { continue; }
                $already_in = FALSE;
                foreach ($this->states as $state) {
                    if ($state->__eq($jump)) {
                        $already_in = TRUE;
                        $jump = $state;
                        break;
                    }
                }

                if (!$already_in) {
                    $this->states[] = $jump;
                }
                
                $this->jumps[] = new PaccLRJump($this->states[$i], $symbol, $jump);
            }
        }
    }

    /**
     * @return void
     */
    private function computeTable()
    {
        for ($state = 0, $len = count($this->states); $state < $len; ++$state) {
            $items = $this->states[$state];

            // shifts
            foreach ($this->grammar->terminals as $terminal) {
                $do_shift = FALSE;

                foreach ($items as $item) {
                    if (current($item->afterDot()) !== FALSE &&
                        current($item->afterDot())->__eq($terminal))
                    {
                        $do_shift = TRUE;
                        break;
                    }
                }

                if ($do_shift) {
                    $this->table[$state * $this->table_pitch + $terminal->index] =
                        $this->getNextState($items, $terminal);
                    if ($this->table[$state * $this->table_pitch + $terminal->index] === NULL) {
                        throw new Exception('Cannot get next state for shift.');
                    }
                }
            }

            // reduces/accepts
            foreach ($items as $item) {
                if (count($item->afterDot()) > 0) { continue; }
                $tableindex = $state * $this->table_pitch + $item->terminalindex;

                if ($item->production->__eq($this->grammar->startProduction)) { // accept
                    $this->table[$tableindex] = 0;
                } else {
                    if (isset($this->table[$tableindex])) {
                        if ($this->table[$tableindex] > 0) {
                            throw new Exception('Shift-reduce conflict.');
                        } else if ($this->table[$tableindex] < 0) {
                            throw new Exception('Reduce-reduce conflict: ' . $item);
                        } else {
                            throw new Exception('Accpet-reduce conflict: ' . $item);
                        }
                    }

                    $this->table[$tableindex] = -$item->production->index;
                }
            }

            // gotos
            foreach ($this->grammar->nonterminals as $nonterminal) {
                $this->table[$state * $this->table_pitch + $nonterminal->index] =
                    $this->getNextState($items, $nonterminal);
            }
        }
    }

    /**
     * @return int
     */
    private function getNextState(PaccSet $items, PaccSymbol $symbol)
    {
        if ($items->getType() !== 'PaccLRItem') {
            throw new InvalidArgumentException(
                'Bad type - expected PaccSet<LRItem>, given PaccSet<' .
                $items->getType() . '>.'
            );
        }

        foreach ($this->jumps as $jump) {
            if ($jump->from->__eq($items) && $jump->symbol->__eq($symbol)) {
                for ($i = 0, $len = count($this->states); $i < $len; ++$i) {
                    if ($jump->to->__eq($this->states[$i])) {
                        return $i;
                    }
                }
            }
        }

        return NULL;
    }

    /**
     * @return PaccSet<PaccLRItem>
     */
    private function closure(PaccSet $items)
    {
        if ($items->getType() !== 'PaccLRItem') {
            throw new InvalidArgumentException(
                'Bad type - expected PaccSet<LRItem>, given PaccSet<' .
                $items->getType() . '>.'
            );
        }

        do {
            $done = TRUE;

            $itemscopy = clone $items;

            foreach ($items as $item) {
                if (!(count($item->afterDot()) >= 1 &&
                    current($item->afterDot()) instanceof PaccNonterminal))
                {
                    continue;
                }

                $newitems = new PaccSet('PaccLRItem');
                $beta_first = new PaccSet('integer');
                if (count($item->afterDot()) > 1) {
                    $beta_first->add(next($item->afterDot())->first);
                    $beta_first->delete($this->grammar->epsilon->index);
                }

                if ($beta_first->isEmpty()) {
                    $beta_first->add($item->terminalindex);
                }
                $B = current($item->afterDot());

                foreach ($this->grammar->productions as $production) {
                    if ($B->__eq($production->left)) {
                        foreach ($beta_first as $terminalindex) {
                            $newitems->add(new PaccLRItem($production, 0, $terminalindex));
                        }
                    }
                }

                if (!$newitems->isEmpty() && !$itemscopy->contains($newitems)) {
                    $itemscopy->add($newitems);
                    $done = FALSE;
                }
            }

            $items = $itemscopy;

        } while (!$done);

        return $items;
    }

    /**
     * @param PaccSet<PaccLRItem>
     * @param PaccSymbol
     * @return PaccSet<PaccLRItem>
     */
    private function jump(PaccSet $items, PaccSymbol $symbol)
    {
        if ($items->getType() !== 'PaccLRItem') {
            throw new InvalidArgumentException(
                'Bad type - expected PaccSet<LRItem>, given PaccSet<' .
                $items->getType() . '>.'
            );
        }

        $ret = new PaccSet('PaccLRItem');

        foreach ($items as $item) {
            if (!(current($item->afterDot()) !== FALSE &&
                current($item->afterDot())->__eq($symbol)))
            {
                continue;
            }

            $ret->add(new PaccLRItem($item->production, $item->dot + 1, $item->terminalindex));
        }

        return $this->closure($ret);
    }
}
