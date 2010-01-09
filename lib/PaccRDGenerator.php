<?php
/**
 * Generates recursive descent parser
 */
class PaccRDGenerator
{
    /**
     * Grammar
     * @var PaccGrammar
     */
    private $grammar;

    /**
     * Generated string
     * @var string
     */
    private $generated = NULL;

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
     * Initializes instance
     * @param PaccGrammar
     */
    public function __construct(PaccGrammar $grammar)
    {
        $this->grammar = $grammar;

        foreach (array('header', 'inner', 'footer', 'indentation', 'eol', 'terminals_prefix', 'parse') as $name) {
            if (isset($grammar->options[$name])) {
                $this->$name = $grammar->options[$name];
            }
        }
    }

    /**
     * Generates parser
     * @return string
     */
    public function generate()
    {
        if ($this->generated === NULL) {
            $this->generated = '';
            $this->generated .= '<?php' . $this->eol;

            if (strpos($this->grammar->name, '\\') === FALSE) { $classname = $this->grammar->name; }
            else {
                $namespace = explode('\\', $this->grammar->name);
                $classname = array_pop($namespace);
                $this->generated .= 'namespace ' . implode('\\', $namespace) . ';' . $this->eol;
            }

            $this->generated .= $this->header . $this->eol;
            $this->generated .= 'class ' . $classname . $this->eol . '{' . $this->eol;
            $this->generated .= $this->phpize($this->treeize($this->grammar->productions)) . $this->eol;
            $this->generated .= $this->inner . $this->eol;
            $this->generated .= '}' . $this->eol;
            $this->generated .= $this->footer;
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
     * @param PaccSet<PaccProduction>
     * @return array
     */
    protected function treeize($productions)
    {
        $ret = array();

        foreach ($productions as $production) {
            if (!isset($ret[$production->left->name])) { $ret[$production->left->name] = array(); }
            $cur =& $ret[$production->left->name];

            foreach ($production->right as $symbol) {
                if ($symbol instanceof PaccNonterminal) {
                    $type = 'N';
                    $value = $symbol->name;

                } else {
                    assert($symbol instanceof PaccTerminal);

                    if ($symbol->type !== NULL) {
                        $type = 'T';
                        $value = $symbol->type;
                    } else {
                        assert($symbol->value !== NULL);
                        $type = 'S';
                        $value = $symbol->value;
                    }
                }

                $k = $type . ':' . $value;
                if (!isset($cur[$k])) { $cur[$k] = array(); }
                $cur =& $cur[$k];
            }

            if ($production->code !== NULL) { $cur['$'] = trim($production->code); }
            else if (!empty($terms))        { $cur['$'] = '$$ = $1;'; }
            else                            { $cur['$'] = '$$ = TRUE;'; }
        }

        foreach ($ret as &$rule) { $rule = $this->treelifting($rule); }

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
    protected function phpize($treeish_rules, $indentation = NULL)
    {
        if ($indentation === NULL) { $indentation = $this->indentation; }

        $ret = '';

        foreach ($treeish_rules as $name => $rule_tree) {
            $ret .= $indentation . 'private function _' . $name . '_() {' . $this->eol;
            $ret .= $indentation . $this->indentation . $this->phpizeVariables('$$ = TRUE;') . $this->eol;

            $ret .= $this->phpizeRuleTree($rule_tree, $indentation . $this->indentation);

            $ret .= $indentation . $this->indentation . $this->phpizeVariables('return $$;') . $this->eol;
            $ret .= $indentation . '}' . $this->eol . $this->eol;
        }

        $ret .= $indentation . 'private function ' . $this->parse . '() {' . $this->eol;
        reset($treeish_rules);
        $ret .= $indentation . $this->indentation . 'return $this->_' . key($treeish_rules) . '_();' . $this->eol;
        $ret .= $indentation . '}' . $this->eol;

        foreach (array('currentToken', 'currentTokenType', 'currentTokenLexeme', 'nextToken') as $method) {
            if (isset($this->grammar->options[$method])) {
                $ret .= $indentation . 'private function _' . $method . '() {' . $this->eol;
                $ret .= $this->grammar->options[$method] . $this->eol;
                $ret .= $indentation . '}' . $this->eol . $this->eol;
            }
        }

        return $ret;
    }

    /**
     * Converts one rule tree to PHP
     * @param array|string
     * @param string
     * @param int
     * @return string
     */
    protected function phpizeRuleTree($tree, $indentation = NULL, $i = 1)
    {
        if ($indentation === NULL) { $indentation = $this->indentation; }

        if (is_string($tree)) {
            $lines = array();
            foreach (explode($this->eol, $tree) as $line) {
                $lines[] = $indentation . trim($line);
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
                    $cond = '$this->_currentTokenLexeme() === ' . $s;
                    $current_token = TRUE;
                break;

                case 'T':
                    $t = $this->phpizeTerminal(substr($k, 2));
                    $cond = '$this->_currentTokenType() === ' . $t;
                    $current_token = TRUE;
                break;

                case 'N':
                    $n = '_' . substr($k, 2) . '_';
                    $cond = $this->phpizeVariables('($' . $i) .' = $this->' . $n . '()) !== NULL';
                break;
            }

            $ret .= $indentation . (!$first ? 'else ' : '') . 'if (' . $cond . ') {' . $this->eol;
            if ($current_token) {
                $ret .= $indentation . $this->indentation . $this->phpizeVariables('$' . $i . ' = $this->_currentToken();') . $this->eol;
                $ret .= $indentation . $this->indentation . '$this->_nextToken();' . $this->eol;
            }
            $ret .= $this->phpizeRuleTree($v, $indentation . $this->indentation, $i + 1) . $this->eol;
            $ret .= $indentation . '}' . $this->eol;
            $first = FALSE;
        }

        if (!$first) { $ret .= $indentation . 'else {' . $this->eol; }
        if ($else === NULL) { $ret .= $indentation . $this->indentation . $this->phpizeVariables('$$ = NULL;') . $this->eol; }
        else { $ret .= $this->phpizeRuleTree($else, $indentation . $this->indentation) . $this->eol; }
        if (!$first) { $ret .= $indentation . '}' . $this->eol; }

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
        return $this->terminals_prefix . $t;
    }
}
