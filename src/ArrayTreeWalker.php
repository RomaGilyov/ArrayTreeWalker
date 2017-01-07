<?php

namespace RGilyov;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;

class ArrayTreeWalker implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var array
     */
    protected $tree;

    /**
     * @var null
     */
    protected $nodeName;

    /**
     * Tree constructor.
     * @param array $tree
     * @param null $nodeName
     */
    public function __construct(array $tree, $nodeName = null)
    {
        $this->tree = $tree;
        $this->nodeName = $nodeName;
    }

    /**
     * @param $name
     * @return static
     */
    public function __get($name)
    {
        if (!is_null($this->nodeName) && isset($this->tree[$this->nodeName])) {
            return $this->processGet($this->tree[$this->nodeName], $name);
        }

        return $this->processGet($this->tree, $name);
    }

    /**
     * @param $tree
     * @param $name
     * @return static
     */
    protected function processGet($tree, $name)
    {
        if (isset($tree[$name])) {
            if (is_array($tree[$name])) {
                return new static($tree[$name], $this->nodeName);
            }

            return new static([$name => $tree[$name]], $this->nodeName);
        }

        return new static([], $this->nodeName);
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function __set($name, $value)
    {
        if (is_null($name)) {
            $this->tree[] = $value;
        } else {
            $this->tree[$name] = $value;
        }

        return $this;
    }

    /**
     * @param $field
     * @return array|null
     */
    public function get($field)
    {
        return (isset($this->tree[$field])) ? $this->tree[$field] : null;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->tree;
    }

    /////////////////////////////////////////// array access implementation //////////////////////////

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->tree[] = $value;
        } else {
            $this->tree[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->tree[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->tree[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return isset($this->tree[$offset]) ? $this->tree[$offset] : null;
    }

    //////////////////////////////////////// countable implementation ////////////////////////////

    /**
     * @return int
     */
    public function count()
    {
        return count($this->tree);
    }

    //////////////////////////////////////// iterator implementation ///////////////////////////////

    /**
     * Get an iterator for the items.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->tree);
    }
}
