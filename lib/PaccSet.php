<?php
/**
 * Set implementation - always holds only one copy of some value
 */
class PaccSet implements Iterator, Countable
{
    /**
     * All values in set
     * @var array
     */
    private $set = array();

    /**
     * Allowed types of values
     * @var string
     */
    private $type;

    /**
     * Initializes set
     * @param string
     */
    public function __construct($type = NULL)
    {
        if (!is_string($type)) {
            throw new InvalidArgumentException('Type has to be a string.');
        }

        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function __eq($o)
    {
        if ($o instanceof self && count($o->set) === count($this->set)) {
            foreach ($o as $item) {
                if (!$this->contains($item)) { return FALSE; }
            }

            return TRUE;
        }

        return FALSE;
    }

    public function __toString()
    {
        $ret = "{\n";
        foreach ($this->set as $item) { $ret .= '    ' . (string) $item . "\n"; }
        $ret .= "}\n";
        return $ret;
    }

    /**
     * Return allowed type
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Add item to set
     * @param mixed
     */
    public function add($item)
    {
        if ($item instanceof self) {
            foreach ($item->set as $i) {
                $this->add($i);
            }

            return;
        }

        $this->checkType($item);
        $hash = $this->hash($item);
        if (!isset($this->set[$hash]) && $this->tryEq($item) === NULL) {
            $this->set[$hash] = $item;
        }
    }

    /**
     * Check if item is in set
     * @param mixed
     * @return bool
     */
    public function contains($item)
    {
        if ($item instanceof self) {
            foreach ($item->set as $i) {
                if (!$this->contains($i)) { return FALSE; }
            }

            return TRUE;
        }

        $this->checkType($item);
        $hash = $this->hash($item);
        return isset($this->set[$hash]) || $this->tryEq($item) !== NULL;
    }

    /**
     * Delete item from set
     * @param mixed
     */
    public function delete($item)
    {
        $this->checkType($item);
        $hash = $this->hash($item);
        if (isset($this->set[$hash])) { unset($this->set[$hash]); }
        else {
            if (($hash = $this->tryEq($item)) !== NULL) {
                $this->set[$hash];
            }
        }
    }

    /**
     * Find item
     * @param mixed
     * @return mixed
     */
    public function find($item)
    {
        $this->checkType($item);
        $hash = $this->hash($item);
        if (!isset($this->set[$hash]) && ($hash = $this->tryEq($item)) === NULL) {
            return NULL;
        }
        return $this->set[$hash];
    }

    /**
     * Check if set is empty
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->set) === 0;
    }

    /**
     * Rewind
     */
    public function rewind()
    {
        reset($this->set);
    }

    /**
     * Get current item
     * @return mixed
     */
    public function current()
    {
        return current($this->set);
    }

    /**
     * Get current key
     * @return mixed
     */
    public function key()
    {
        return current($this->set);
    }

    /**
     * Get next item
     * @return mixed
     */
    public function next()
    {
        return next($this->set);
    }

    /**
     * Check is there are more items
     * @return bool
     */
    public function valid()
    {
        return current($this->set) !== FALSE;
    }

    /**
     * Count items
     * @return int
     */
    public function count()
    {
        return count($this->set);
    }

    /**
     * Checks type
     * @param mixed
     * @return void
     */
    private function checkType($val)
    {
        if (is_resource($val) || gettype($val) === 'unknown type') {
            throw new InvalidArgumentException(
                'Bad type - resource unsupported or uknown type'
            );
        }

        if ($this->type !== NULL && !(gettype($val) === $this->type ||
            (is_object($val) && $val instanceof $this->type)))
        {
            throw new InvalidArgumentException(
                'Bad type - expected ' .
                $this->type .
                ', given ' .
                (gettype($val)) . (is_object($val) ? ' (' . get_class($val) . ')' : '') .
                '.'
            );
        }
    }

    /**
     * Get hash of given value
     * @param mixed
     * @return string MD5 hash
     */
    private function hash($val)
    {
        if (is_array($val)) {
            $a = array();
            foreach ($val as $k => $v) {
                $a[] = md5(gettype($k) . ':' . $k) . $this->hash($v);
            }
            $ret = md5(implode(',', $a));

        } else if (is_object($val)) {
            $ret = spl_object_hash($val);

        } else {
            $ret = md5(gettype($val) . ':' . ((string) $val));
        }

        return $ret;
    }

    /**
     * Tries if val has `__eq` method and if does, then checks if some item in set is 
     * not equal
     * @param mixed
     * @return string hash of equal item in set
     */
    private function tryEq($val)
    {
        $ret = NULL;

        if (is_object($val) && method_exists($val, '__eq')) {
            foreach ($this->set as $hash => $item) {
                if ($val->__eq($item)) {
                    $ret = $hash;
                    break;
                }
            }
        }

        return $ret;
    }
}
