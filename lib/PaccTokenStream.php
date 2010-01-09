<?php
/**
 * Token stream
 */
interface PaccTokenStream
{
    /**
     * @return PaccToken
     */
    function current();

    /**
     * @return PaccToken
     */
    function next();
}
