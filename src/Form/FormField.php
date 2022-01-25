<?php


namespace Asivas\ABM\Form;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;
use JsonSerializable;
use phpDocumentor\Reflection\Types\ClassString;
use function PHPUnit\Framework\isEmpty;


class FormField implements ArrayAccess, Arrayable, Jsonable, JsonSerializable
{
    protected $name;
    protected $label;
    protected $type;
    protected $modelItem;
    protected $required;
    /**
     * @var string|ClassString|null Resource class name
     */
    protected $resource;
    protected $options;
    protected $gridClasses;
    protected $mode;
    protected $filter;
    protected $rules;
    protected $errorMesg;


    public function __construct($name,$label,$type,$required=null,$gridClasses=null,$resource = null,$modelItem = null,$options=null,$mode=null)
    {
        $this->name = $name;
        $this->label =$label;
        $this->type = $type;
        $this->modelItem = $modelItem;
        $this->resource = $resource;
        $this->options = $options;
        $this->gridClasses = $gridClasses;
        $this->mode = $mode;
        $this->required = $required;
        $this->rules = [];
    }

    static function create($name,$label,$type) {
      return new static($name,$label,$type);
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
     * @return FormField
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
     * @return FormField
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return FormField
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getModelItem()
    {
        if(empty($this->modelItem))
            return $this->name;
        return $this->modelItem;
    }

    /**
     * @param mixed $modelItem
     * @return FormField
     */
    public function setModelItem($modelItem)
    {
        $this->modelItem = $modelItem;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param mixed $resource
     * @return FormField
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getOptions()
    {
        if(empty($this->options) && $this->getType()=='select' && (!empty($this->resource) || isset($this->filter) ))
            $this->options = $this->getResourceOptions();
        return $this->options;
    }

    /**
     * @param mixed|null $options
     * @return FormField
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getGridClasses()
    {
        return $this->gridClasses;
    }

    /**
     * @param mixed|null $gridClasses
     * @return FormField
     */
    public function setGridClasses($gridClasses)
    {
        $this->gridClasses = $gridClasses;
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param mixed|null $mode
     * @return FormField
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @param mixed $required
     * @return FormField
     */
    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }

    public function setErrorMessage($message)
    {
        $this->errorMesg = $message;
    }

    public function getErrorMessage()
    {
        return $this->errorMesg;
    }

    public function addRule($rule)
    {
        $this->rules[] = $rule;
        return $this;
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function validate($value)
    {
        if(empty($this->rules)){
            return true;
        }
        /** @var Rule $rule */
        foreach ($this->rules as $rule){
            $validRule = $rule->validate($value);
            if(!$validRule){
                $this->setErrorMessage($rule->getErrorMessage());
                return false;
            }
        }
        return true;
    }


    public function toArray() {
        $arr = [
            'name'=>$this->getName(),
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'modelItem' => $this->getModelItem(),
            'required' => $this->getRequired()
        ];
        if(isset($this->resource))
            $arr['resource'] =  Str::plural(Str::lower(class_basename($this->getResource())));
        if(!empty($this->getOptions()))
            $arr['options'] = $this->getOptions();
        if(isset($this->gridClasses))
            $arr['gridClasses'] = $this->gridClasses;
        if(isset($this->mode))
            $arr['mode'] = $this->mode;
        return $arr;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        $arr = $this->toArray();
        return isset($arr[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        $arr = $this->toArray();
        return $arr[$offset];
        // TODO: Implement offsetGet() method.
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        if(isset($this->$offset))
            unset($this->$offset);
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
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param mixed $filter
     * @return FormField
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
        return $this;
    }


    public function getResourceOptions() {
        if(isset($this->filter))
            $q = $this->filter;
        else
            $q = $this->resource::query();

        $list = $q->get();
        $options = [];
        foreach ( $list as $resource ) {
            $options[] = [
                'value'=>$resource->id,
                'text' =>$resource->label
            ];
        }
        return $options;
    }

}
