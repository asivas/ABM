<?php

namespace ABM\Http;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class ColumnField implements ArrayAccess, Arrayable, Jsonable, JsonSerializable
{

    protected $name;
    protected $label;

    protected $fieldType;

    public function __construct($name,$label)
    {
        $this->label = $label;
        $this->name = $name;
        $this->fieldType = ColumnFieldType::modelField; //every ColumnField is model By Default
    }

    static function create($name, $label) {
        return new static($name, $label);
    }

    public function toArray()
    {
        return [$this->label => $this->name];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize());
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function offsetExists($offset)
    {
        $arr = $this->toArray();
        return isset($arr[$offset]);
    }

    public function offsetGet($offset)
    {
        $arr = $this->toArray();
        return $arr[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        if(isset($this->$offset))
            unset($this->$offset);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return ColumnField
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     * @return ColumnField
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * @param $fieldType
     * @return ColumnField
     */
    public function setFieldType($fieldType)
    {
        $this->fieldType = $fieldType;
        return $this;
    }


}
