<?php namespace Mithra\Needle\Migrations;

trait ArrayAccessTrait {

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        if(isset($this->attributes[$key]))
        {
            return $this->attributes[$key];
        }
    }

    /**
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Determine if an attribute exists
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }
    /**
     * Unset an attribute
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);

    }
    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;

    }
    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;

    }
    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);

    }

    public function only($keys = [])
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = [];

        foreach($keys as $key)
        {
            array_set($results, $key, array_get($this->attributes, $key));
        }

        return $results;
    }

    public function except($keys = [])
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = [];

        foreach($this->attributes as $key => $value)
        {
            if( ! in_array($key, $keys))
            {
                $results[$key] = $value;
            }
        }

        return $results;
    }
}
