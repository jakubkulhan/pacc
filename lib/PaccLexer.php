<?php
/**
 * Converts string into stream of tokens
 */
class PaccLexer implements Iterator
{
    const
        IDREGEX = '/^([a-zA-Z][a-zA-Z_]*)/',
        STRINGREGEX = '/^(\'[^\']+\'|"[^"]+"|`[^`]+`)/',
        SPECIALREGEX = '/^(:|\||\{|\}|;)/';

    /**
     * Conversion between special characters and token types
     * @var array
     */
    private static $conv = array(
        ':' => PaccToken::RULESTART,
        ';' => PaccToken::RULEEND,
        '|' => PaccToken::ALTER,
        '{' => PaccToken::CODESTART,
        '}' => PaccToken::CODEEND,
    );

    /**
     * Tokens
     * @var array
     */
    private $tokens = array();

    /**
     * Initializes lexer
     * @param string
     * @param int
     */
    public function __construct($s, $start_line = 1)
    {
        $line = $start_line;
        $pos = 1;

        $s = str_replace(array("\r\n", "\r"), "\n", $s);

        while (!empty($s)) {
            if (preg_match('/^(\s+)/', $s, $m)) {
                // intentionally left blank

            } else if (preg_match(self::IDREGEX, $s, $m)) {
                $this->tokens[] = new PaccToken(PaccToken::ID, $m[1], $line, $pos);

            } else if (preg_match(self::STRINGREGEX, $s, $m)) {
                $this->tokens[] = new PaccToken(PaccToken::STRING, $m[1], $line, $pos);

            } else if (preg_match(self::SPECIALREGEX, $s, $m)) {
                $this->tokens[] = new PaccToken(self::$conv[$m[1]], $m[1], $line, $pos);

                if (end($this->tokens)->type === PaccToken::CODESTART) {
                    $offset = 0;
                    do {
                        if (($rbrace = strpos($s, '}', $offset)) === FALSE) {
                            $this->tokens[] = new PaccToken(NULL, substr($s, 1), $line, $pos + 1);
                            return ;
                        }

                        $offset = $rbrace + 1;
                        $code = substr($s, 1, $rbrace - 1);

                    } while (substr_count($code, '{') !== substr_count($code, '}'));

                    $this->tokens[] = new PaccToken(PaccToken::CODE, $code, $line, $pos + 1);
                    $m[1] .= $code;
                }

            } else {
                $m = array(1 => $s[0]);
                $this->tokens[] = new PaccToken(NULL, $m[1], $line, $pos);
            }

            $lines = substr_count($m[1], "\n");
            $line += $lines;
            if ($lines > 0) { $pos = strlen(end(explode("\n", $m[1]))) + 1; }
            else { $pos += strlen($m[1]); }
            $s = substr($s, strlen($m[1]));
        }

        reset($this->tokens);
    }

    /**
     * Returns current token
     * @return PaccToken
     */
    public function current()
    {
        return current($this->tokens);
    }

    /**
     * Returns current key
     * @return int
     */
    public function key()
    {
        return key($this->tokens);
    }

    /**
     * Advances token stream pointer
     * @return PaccToken new current token
     */
    public function next()
    {
        return next($this->tokens);
    }

    /**
     * Rewinds token strem's pointer one place
     * @return PaccToken new current token
     */
    public function prev()
    {
        return prev($this->tokens);
    }

    /**
     * Rewinds stream's pointer
     * @return PaccToken returns first token
     */
    public function rewind()
    {
        return reset($this->tokens);
    }

    /**
     * Checks if there are any more tokens in stream
     * @return bool TRUE if there are still some tokens to process
     */
    public function valid()
    {
        return $this->current() !== FALSE;
    }

    /**
     * Creates instance from string
     * @param string
     * @param int
     * @return self
     */
    public static function fromString($s, $start_line = 1)
    {
        return new self($s, $start_line);
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
