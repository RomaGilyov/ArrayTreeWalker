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
    protected $bindingName;

    /**
     * @var string
     */
    protected $hash;

    /**
     * @var array
     */
    protected static $meta;

    /**
     * @var string
     */
    const LEVELS = 'levels';

    /**
     * @var string
     */
    const INDEXES = 'indexes';

    /**
     * Tree constructor.
     * @param $tree
     * @param string $bindingName
     * @param null $hash
     */
    public function __construct($tree, $bindingName = 'edges', $hash = null)
    {
        $this->tree = method_exists($tree, 'toArray') ? $tree->toArray() : (array)$tree;

        $this->bindingName = $bindingName;

        $this->hash = is_string($hash) ? $hash : spl_object_hash($this);
    }

    /**
     * @param null $key
     */
    public function clearMeta($key = null)
    {
        if (! is_null($key)) {
            static::$meta[$this->hash][$key] = null;
        }

        static::$meta[$this->hash] = null;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function setMeta($key, $value)
    {
        return static::$meta[$this->hash][$key] = $value;
    }

    /**
     * @param $key
     * @param $metaKey
     * @param $value
     */
    public function putMeta($key, $metaKey, $value)
    {
        if (($meta = $this->getMeta($key)) && is_array($meta)) {
            $meta[$metaKey] = $value;
            $this->setMeta($key, $meta);
        } else {
            $this->setMeta($key, [$metaKey => $value]);
        }
    }

    /**
     * @param $key
     * @return null
     */
    public function getMeta($key = null)
    {
        $meta = isset(static::$meta[$this->hash]) ? static::$meta[$this->hash] : null;

        if (! is_null($key) && ! is_null($meta)) {
            return isset($meta[$key]) ? $meta[$key] : null;
        }

        return $meta;
    }

    /**
     * @param $name
     * @return static
     */
    public function __get($name)
    {
        if (!is_null($this->bindingName) && isset($this->tree[$this->bindingName])) {
            return $this->processGet($this->tree[$this->bindingName], $name);
        }

        return $this->processGet($this->tree, $name);
    }

    /**
     * @param $tree
     * @param $name
     * @return static
     */
    protected function processGet(&$tree, $name)
    {
        if (isset($tree[$name])) {
            if (is_array($tree[$name])) {
                return new static($tree[$name], $this->bindingName);
            }

            return new static([$name => $tree[$name]], $this->bindingName);
        }

        return new static([], $this->bindingName);
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
     * @param $field
     * @return mixed|null
     */
    public function &getByReference($field)
    {
        if (isset($this->tree[$field])) {
            $return = &$this->tree[$field];
        } else {
            $return = null;
        };

        return $return;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->tree;
    }

    /////////////////////////////////////////// Tree aggregations ////////////////////////////////////

    /**
     * @param $tree
     * @param \Closure $handler
     * @param int $level
     * @param int $parentNodeNumber
     * @throws \Exception
     */
    protected function eachRecursive(&$tree, \Closure $handler, $level = 0, $parentNodeNumber = 0)
    {
        if (! is_int($level) && ! is_int($parentNodeNumber)) {
            throw new \Exception(
                'The `each` method expects only an integer for second and third arguments.
                 Never pass the parameters unless you want the all calculations work incorrectly.'
            );
        }

        if ($level === 0) {
            $this->resolveHandler($tree, $handler, $level, 0, 0);
            static::$meta['0.0'] = 0;
        }

        if (static::hasEdgesStatic($tree, $this->bindingName)) {

            $edges = &$tree[$this->bindingName];

            ++$level;

            $index = 0;

            $indexes = $this->getMeta(static::INDEXES);

            while (isset($indexes[$level.'.'.$index])) {
                ++$index;
            }

            foreach ($edges as &$innerTree) {
                $this->putMeta(static::INDEXES, $level.'.'.$index, $parentNodeNumber);

                if ($this->resolveHandler($innerTree, $handler, $level, $index, $parentNodeNumber) === false) {
                    break;
                }

                $this->eachRecursive($innerTree, $handler, $level, $index);

                ++$index;
            }
        }
    }

    /**
     * @param $tree
     * @param \Closure $handler
     * @param $level
     * @param $nodeNumber
     * @param $parentNodeNumber
     * @return array
     */
    protected function resolveHandler(&$tree, \Closure $handler, $level, $nodeNumber, $parentNodeNumber)
    {
        $result = $handler(
            static::cutEdgesStatic($tree, $this->bindingName)->toArray(),
            $level,
            $nodeNumber,
            $parentNodeNumber
        );

        if (is_array($result)) {
            $tree = array_replace($tree, $result);
        }

        return $result;
    }

    /**
     * @param \Closure $handler
     * @return $this
     * @throws \Exception
     */
    public function walkDown(\Closure $handler)
    {
        $this->clearMeta(static::INDEXES);

        $this->eachRecursive($this->tree, $handler);

        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     * @return null
     */
    public static function __callStatic($name, $arguments)
    {
        if (strpos($name, 'Static') !== false) {
            $name = str_replace('Static', '', $name);
        }

        $tree = new static(...$arguments);

        if (method_exists($tree, $name)) {
            return $tree->{$name}();
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function cutEdges()
    {
        $tree = $this->toArray();

        if ($this->hasEdges()) {
            unset($tree[$this->bindingName]);
        }

        return new static($tree, $this->bindingName);
    }

    /**
     * @return bool
     */
    public function hasEdges()
    {
        return isset($this->tree[$this->bindingName]) && is_array($this->tree[$this->bindingName]);
    }

    /**
     * @return bool
     */
    public function isLeaf()
    {
        return ! $this->hasEdges();
    }

    /**
     * @return array|null
     */
    public function getEdges()
    {
        return $this->hasEdges() ? $this->get($this->bindingName) : null;
    }

    /**
     * @return array|null
     */
    public function &getEdgesByReference()
    {
        if ($this->hasEdges()) {
            $return = &$this->getByReference($this->bindingName);
        } else {
            $return = null;
        }

        return $return;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function treeLevels()
    {
        if ($levels = $this->getMeta(static::LEVELS)) {
            return $levels;
        }

        $levels = [];

        $this->walkDown(function ($node, $level, $nodeNumber) use (&$levels) {
            $node['graph_index'] = $level.'.'.$nodeNumber;
            $levels[$level][]    = $node;
        });

        return $this->setMeta(static::LEVELS, $levels);
    }

    /**
     * @param $level
     * @param $nodeNumber
     * @return null
     */
    public function getNode($level, $nodeNumber)
    {
        $levels = $this->treeLevels();

        return (isset($levels[$level][$nodeNumber])) ? $levels[$level][$nodeNumber] : null;
    }

    /**
     * @param $level
     * @param $nodeNumber
     * @return null
     */
    public function getParentNodeOf($level, $nodeNumber)
    {
        $this->treeLevels();

        $indexes = $this->getMeta(static::INDEXES);

        if (isset($indexes[$level.'.'.$nodeNumber])) {
            return $this->getNode(--$level, $indexes[$level.'.'.$nodeNumber]);
        }

        return null;
    }

    /**
     * @param $level
     * @param $nodeNumber
     * @return array
     */
    public function getNodeLeafs($level, $nodeNumber)
    {
        $this->treeLevels();

        $indexes = $this->getMeta(static::INDEXES);

        $leafs = [];

        if ($this->getNode($level, $nodeNumber)) {

            $leafsLevel = $level + 1;

            foreach ($indexes as $index => $parentNodeNumber) {
                $leafIndex = explode('.', $index);

                $indexLevel     = (isset($leafIndex[0])) ? $leafIndex[0] : null;
                $leafNodeNumber = (isset($leafIndex[1])) ? $leafIndex[1] : null;

                if (($indexLevel == $leafsLevel) && ($parentNodeNumber == $nodeNumber)) {
                    $leafs[] = $this->getNode($indexLevel, $leafNodeNumber);
                }
            }
        }

        return $leafs;
    }

    /**
     * @param \Closure $handler
     * @param null $nodeId
     * @return array
     */
    public function walkUpParameters(\Closure $handler, $nodeId = null)
    {
        $levels = $this->treeLevels();

        $walkOn = count($levels) - 1;

        $parameters = [];

        for ($level = $walkOn; $level >= 0; $level--) {
            if (isset($levels[$level]) && is_array($levels[$level])) {
                foreach ($levels[$level] as $nodeNumber => $node) {

                    $id = (isset($node[$nodeId])) ? $node[$nodeId] : $level.'.'.$nodeNumber;

                    $parameters[$id] = $handler(
                        $node,
                        $this->getNodeLeafs($level, $nodeNumber),
                        $parameters,
                        $level,
                        $nodeNumber
                    );

                }
            }
        }

        return $parameters;
    }

    /**
     * @param \Closure $handler
     * @param null $nodeId
     * @return $this
     */
    public function walkUp(\Closure $handler, $nodeId = null)
    {
        $parameters = $this->walkUpParameters($handler, $nodeId);

        return $this->attachParametersToNodes($parameters, $nodeId);
    }

    /**
     * @param $parameters
     * @param null $nodeId
     * @return $this
     */
    public function attachParametersToNodes($parameters, $nodeId = null)
    {
        $this->walkDown(function ($node, $level, $nodeNumber) use ($parameters, $nodeId) {

            $id = (isset($node[$nodeId])) ? $node[$nodeId] : $level.'.'.$nodeNumber;

            return (isset($parameters[$id])) ? (array)$parameters[$id] : null;

        });

        return $this;
    }

    /**
     * @return array
     */
    public function flattenTree()
    {
        return ($levels = $this->treeLevels()) ? array_merge(...$levels) : [];
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
