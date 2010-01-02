<?php
/**
 * Generates parser
 */
class PaccGenerator
{
    /**
     * Will be places before generated parser
     * @var string
     */
    private $head;

    /**
     * Parsed rules
     * @var array
     */
    private $rules;

    /**
     * Will be placed after generated parser
     * @var string
     */
    private $tail;

    /**
     * Generated string
     * @var string
     */
    private $generated = NULL;

    /**
     * One indentation level
     * @var string
     */
    public $indent = '    ';

    /**
     * End of line
     * @var string
     */
    public $eol = PHP_EOL;

    /**
     * Prefix for terminals
     * @var string
     */
    public $terminal_prefix = 'self::';

    /**
     * Initializes instance
     * @param string
     * @param string
     * @param string
     */
    public function __construct($head, $rules_string, $tail)
    {
        $this->head = $head;

        $lexer = PaccLexer::fromString($rules_string, substr_count($head, $this->eol) + 2);
        foreach ($lexer as $token) {
            if ($token->type === PaccToken::ID) {
                if (!preg_match('~^([a-z][a-z_]*|[A-Z][A-Z_]*)$~', $token->content)) {
                    throw new PaccBadIdentifier($token);
                }
            }
        }

        $parser = new PaccParser($lexer);
        $this->rules = $parser->parse();

        $this->tail = $tail;
    }

    /**
     * Generates parser
     * @return string
     */
    public function generate()
    {
        if ($this->generated === NULL) {
            $this->generated = $this->head . $this->eol . 
                $this->phpize($this->treeize($this->rules)) . $this->eol .
                $this->tail;
        }

        return $this->generated;
    }

    /**
     * Generates parser
     * @return string
     */
    public function __toString()
    {
        return $this->generate();
    }

    /**
     * Writes generated output to file
     * @param string|resource
     * @return int|bool bytes written, FALSE on failure
     */
    public function writeToFile($file)
    {
        if (is_string($file)) {
            return @file_put_contents($file, $this->generate());
        } else if (is_resource($file) && get_resource_type($file) === 'file') {
            return @fwrite($file, $this->generate());
        }

        throw new BadMethodCallException('Argument file must be a filename or opened file handle.');
    }

    /**
     * Converts rules to rules tree
     * @param array
     * @return array
     */
    protected function treeize($rules)
    {
        $ret = array();

        foreach ($rules as $name => $expressions) {
            $ret[$name] = array();

            foreach ($expressions as $expression) {
                list($terms, $code) = $expression;
                $cur =& $ret[$name];

                foreach ($terms as $t) {
                    if ($t->type === PaccToken::ID) {
                        if (ord($t->content[0]) >= 65 /* A */ && ord($t->content[0]) <= 90 /* Z */) {
                            $type = 'T';
                        } else { $type = 'N'; }
                        $content = $t->content;
                    } if ($t->type === PaccToken::STRING) {
                        $type = 'S';
                        if ($t->content[0] === '"' || $t->content[0] === '\'') {
                            $content = eval('return ' . $t->content . ';');
                        } else {
                            $content = substr($t->content, 1, strlen($t->content) - 2);
                        }
                    }

                    $k = $type . ':' . $content;
                    if (!isset($cur[$k])) { $cur[$k] = array(); }
                    $cur =& $cur[$k];
                }

                if ($code !== NULL) { $cur['$'] = trim($code->content); }
                else                { $cur['$'] = '$$ = $1;'; }
            }

            $ret[$name] = $this->treelifting($ret[$name]);
        }

        return $ret;
    }

    /**
     * Optimizes rule tree
     * @param array
     * @return array|string
     */
    protected function treelifting($tree)
    {
        if (count($tree) === 1 && isset($tree['$'])) { $tree = $tree['$']; }
        else {
            if (isset($tree['$'])) { 
                $_ = $tree['$']; 
                unset($tree['$']); 
            }

            foreach ($tree as $k => &$v) { $v = $this->treelifting($v); }

            if (isset($_)) {
                $tree['$'] = $_;
            }
        }
        return $tree;
    }

    /**
     * Converts tree to PHP
     * @param array
     * @param string
     * @return string
     */
    protected function phpize($treeish_rules, $indent = NULL)
    {
        if ($indent === NULL) { $indent = $this->indent; }

        $ret = '';

        foreach ($treeish_rules as $name => $rule_tree) {
            $ret .= $indent . 'private function _' . $name . '_() {' . $this->eol;
            $ret .= $indent . $this->indent . $this->phpizeVariables('$$ = TRUE;') . $this->eol;

            $ret .= $this->phpizeRuleTree($rule_tree, $indent . $this->indent);

            $ret .= $indent . $this->indent . $this->phpizeVariables('return $$;') . $this->eol;
            $ret .= $indent . '}' . $this->eol . $this->eol;
        }

        $ret .= $indent . 'protected function doParse() {' . $this->eol;
        reset($treeish_rules);
        $ret .= $indent . $this->indent . 'return $this->_' . key($treeish_rules) . '_();' . $this->eol;
        $ret .= $indent . '}' . $this->eol;

        return $ret;
    }

    /**
     * Converts one rule tree to PHP
     * @param array|string
     * @param string
     * @param int
     * @return string
     */
    protected function phpizeRuleTree($tree, $indent = NULL, $i = 1)
    {
        if ($indent === NULL) { $indent = $this->indent; }

        if (is_string($tree)) {
            $lines = array();
            foreach (explode($this->eol, $tree) as $line) {
                $lines[] = $indent . trim($line);
            }
            return $this->phpizeVariables(implode($this->eol, $lines));
        }

        $ret = '';
        $first = TRUE;
        $else = NULL;
        $open = 1;
        if (isset($tree['$'])) { $else = $tree['$']; unset($tree['$']); }

        foreach ($tree as $k => $v) {

            $cond = '';
            $current_token = FALSE;
            switch ($k[0]) {
                case 'S':
                    $s = var_export(substr($k, 2), TRUE);
                    $cond = '$this->currentTokenContent() === ' . $s;
                    $current_token = TRUE;
                break;

                case 'T':
                    $t = $this->phpizeTerminal(substr($k, 2));
                    $cond = '$this->currentTokenType() === ' . $t;
                    $current_token = TRUE;
                break;

                case 'N':
                    $n = '_' . substr($k, 2) . '_';
                    $cond = $this->phpizeVariables('($' . $i) .' = $this->' . $n . '()) !== FALSE';
                break;
            }

            $ret .= $indent . (!$first ? 'else ' : '') . 'if (' . $cond . ') {' . $this->eol;
            if ($current_token) {
                $ret .= $indent . $this->indent . $this->phpizeVariables('$' . $i . ' = $this->currentToken();') . $this->eol;
                $ret .= $indent . $this->indent . '$this->nextToken();' . $this->eol;
            }
            $ret .= $this->phpizeRuleTree($v, $indent . $this->indent, $i + 1) . $this->eol;
            $ret .= $indent . '}' . $this->eol;
            $first = FALSE;
        }

        if (!$first) { $ret .= $indent . 'else {' . $this->eol; }
        if ($else === NULL) { $ret .= $indent . $this->indent . $this->phpizeVariables('$$ = FALSE;') . $this->eol; }
        else { $ret .= $this->phpizeRuleTree($else, $indent . $this->indent) . $this->eol; }
        if (!$first) { $ret .= $indent . '}' . $this->eol; }

        return $ret;
    }

    /**
     * Converts special variables to PHP variables
     * @param string
     * @return string
     */
    protected function phpizeVariables($s)
    {
        return str_replace('$$', '$__0__', preg_replace('~\$(\d+)~', '$__$1__', $s));
    }

    /**
     * Converts terminal to its PHP form
     * @param string
     * @return string
     */
    protected function phpizeTerminal($t)
    {
        return $this->terminal_prefix . $t;
    }

    /**
     * Creates generator from file
     * @param string
     * @return self
     */
    public static function fromFile($filename)
    {
        return self::fromString(file_get_contents($filename));
    }

    /**
     * Creates generator from string
     * @param string
     * @return self
     */
    public static function fromString($s)
    {
        list($head, $rules, $tail) = explode("---\n", $s);
        return new self($head, $rules, $tail);
    }
}
