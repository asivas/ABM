<?php

namespace Asivas\ABM\Form;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;

class FieldGroup implements \ArrayAccess, \JsonSerializable, \Illuminate\Contracts\Support\Jsonable, \Illuminate\Contracts\Support\Arrayable
{
    protected $fields = [];
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->fields[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->fields;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->fields[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->fields[$offset]);
    }

    #[ArrayShape(['name' => "", 'fields' => "array"])] public function toArray(): array
    {
        $fields = [];
        /**
         * @var FormField $field
         */
        foreach ($this->fields as $pos => $field)
        {

            $fields[] = $field->toArray();
        }

        return ['name'=>$this->name, 'fields'=>$fields];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return mixed
     */
    public function getFields(): mixed
    {
        return $this->fields;
    }

    /**
     * @param mixed $fields
     * @return FieldGroup
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    public function addField(FormField $field) {
        $this->fields[] = $field;
        return $this;
    }

    public function insertField(FormField $field, $position) {
        $this->fields[$position] = $field;
        return $this;
    }

    public function removeField($fieldName) {
        /**
         * @var  $pos
         * @var FormField $field
         */
        foreach ($this->fields as $pos => $field)
        {
            if($field->getName()===$fieldName) {
                unset($this->fields[$pos]);
                return true;
            }
        }
        return false;
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
     * @return FieldGroup
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }



}