<?php
/**
 * Converts string into stream of tokens
 */
class PaccLexer implements PaccTokenStream
{
    /**
     * Mapping from token regexes to token classes
     * @var array
     */
    private static $map = array(
        '/^(\s+)/Ss'                                                    => 'PaccWhitespaceToken',
        '/^([a-zA-Z][a-zA-Z_]*)/S'                                      => 'PaccIdToken',
        '/^(\'(?:\\\'|[^\']+)\'|"(?:\\"|[^"])+"|`(?:\\`|[^`])+`)/SU'    => 'PaccStringToken',
        '/^(@|\\\\|\\.|=|\(|\)|:|\||\{|\}|;)/S'                         => 'PaccSpecialToken',
        '/^(\/\*.*\*\/)/SUs'                                            => 'PaccCommentToken',
        '/^(.)/Ss'                                                      => 'PaccBadToken',
    );

    /**
     * String to tokenize
     * @var string
     */
    private $string = '';

    /**
     * Current token
     * @var PaccToken
     */
    private $current = NULL;

    /**
     * Current line of string to tokenize
     * @var
     */
    private $line = 1;

    /**
     * Current position on current line of string to tokenize
     * @var int
     */
    private $position = 1;

    /**
     * Buffered tokens
     * @var array
     */
    private $buffer = array();

    /**
     * Initializes lexer
     * @param string string to tokenize
     * @param int
     */
    public function __construct($string = '', $start_line = 1)
    {
        $this->line = $start_line;
        $this->string = $string;
    }

    /**
     * Get current token
     * @return PaccToken
     */
    public function current()
    {
        if ($this->current === NULL) { $this->lex(); }
        return $this->current;
    }

    /**
     * Synonynm for lex()
     * @return PaccToken
     */
    public function next()
    {
        return $this->lex();
    }

    /**
     * Get next token
     * @return PaccToken
     */
    public function lex()
    {
        if (!empty($this->buffer)) { return $this->current = array_shift($this->buffer); }
        if (empty($this->string)) { return $this->current = new PaccEndToken(NULL, $this->line, $this->position); }

        foreach (self::$map as $regex => $class) {
            if (!preg_match($regex, $this->string, $m)) { continue; }

            $token = new $class($m[1], $this->line, $this->position);

            if ($token instanceof PaccSpecialToken && $m[1] === '{') {
                $offset = 0;
                do {
                    if (($rbrace = strpos($this->string, '}', $offset)) === FALSE) {
                        array_push($this->buffer, new PaccCodeToken($this->string, $this->line, $this->position + 1));
                        return $this->current = $token;
                    }

                    $offset = $rbrace + 1;
                    $code = substr($this->string, 0, $rbrace + 1);
                    $test = preg_replace($r = '#"((?<!\\\\)\\\\"|[^"])*$
                                          |"((?<!\\\\)\\\\"|[^"])*"
                                          |\'((?<!\\\\)\\\\\'|[^\'])*\'
                                          |\'((?<!\\\\)\\\\\'|[^\'])*$
                                          #x', '', $code);

                } while (substr_count($test, '{') !== substr_count($test, '}'));

                $code = substr($code, 1, strlen($code) - 2);
                array_push($this->buffer, new PaccCodeToken($code, $this->line, $this->position + 1));
                $m[1] .= $code;
            }

            break;
        }

        $lines = substr_count($m[1], "\n") + 
            substr_count($m[1], "\r\n") + substr_count($m[1], "\r");
        $this->line += $lines;

        if ($lines > 0) { $this->position = strlen(end(preg_split("/\r?\n|\r/", $m[1]))) + 1; }
        else { $this->position += strlen($m[1]); }

        $this->string = substr($this->string, strlen($m[1]));

        return $this->current = $token;
    }

    /**
     * Creates instance from string
     * @param string
     * @param int
     * @return self
     */
    public static function fromString($string, $start_line = 1)
    {
        return new self($string, $start_line);
    }

    /**
     * Creates instance from file
     * @param string
     * @return self
     */
    public static function fromFile($filename)
    {
        return self::fromString(file_get_contents($filename));
    }
}
