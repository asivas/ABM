<?php

namespace Asivas\ABM\Http;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class ColumnField implements ArrayAccess, Arrayable, Jsonable, JsonSerializable
{

    protected $name;
    protected $label;

    protected $fieldType;
    protected $valueType;

    protected $minWith;

    public function __construct($name,$label,$valueType = null)
    {
        $this->label = $label;
        $this->name = $name;
        $this->valueType = $valueType;
        $this->fieldType = ColumnFieldType::modelField; //every ColumnField is model By Default
    }

    static function create($name, $label) {
        return new static($name, $label);
    }

    public function toArray()
    {
        return [
            'label' => $this->label,
            'name' => $this->name,
            'type' => $this->valueType,
            'valueType' => $this->valueType,
            'fieldType' => $this->fieldType,
            'value-type' => $this->valueType,
            'field-type' => $this->fieldType,
            'min-width' => $this->minWith,
        ];
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

    /**
     * @return mixed
     */
    public function getValueType()
    {
        return $this->valueType;
    }

    /**
     * @param mixed $valueType
     * @return ColumnField
     */
    public function setValueType($valueType)
    {
        $this->valueType = $valueType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMinWith()
    {
        return $this->minWith;
    }

    /**
     * @param mixed $minWith
     * @return ColumnField
     */
    public function setMinWith($minWith)
    {
        $this->minWith = $minWith;
        return $this;
    }




}
