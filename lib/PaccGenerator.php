<?php
/**
 * Base generator
 */
abstract class PaccGenerator
{
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
     * Generates parser code
     * @return string generated code
     */
    abstract protected function generate();
}
