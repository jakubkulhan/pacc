<?php
/**
 * Filteres some tokens from stream
 */
class PaccTokenFilterOutStream implements PaccTokenStream
{
    /**
     * Stream
     * @var PaccTokenStream
     */
    private $stream;

    /**
     * Filters out there tokens
     * @var array
     */
    private $out;

    /**
     * Initializes filter stream
     * @param PaccTokenStreamable stream to be filtered
     * @param array tokens we do not want
     */
    public function __construct(PaccTokenStream $stream, $out = NULL)
    {
        $this->stream = $stream;
        if (!is_array($out)) { $out = func_get_args(); array_shift($out); }
        $this->out = array_flip($out);
    }

    /**
     * Get current token
     * @retrun PaccToken
     */
    public function current()
    {
        return $this->stream->current();
    }

    /**
     * Get next token
     * @return PaccToken
     */
    public function next()
    {
        do {
            $token = $this->stream->next();
        } while (!($token instanceof PaccEndToken) && isset($this->out[get_class($token)]));
        return $token;
    }
}
