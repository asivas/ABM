<?php


namespace ABM\Http;


class NavItem implements
    \ArrayAccess, \JsonSerializable, \Illuminate\Contracts\Support\Jsonable, \Illuminate\Contracts\Support\Arrayable
{
    protected $path;
    protected $label;
    protected $icon;
    protected $ability;
    protected $resource;

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     * @return NavItem
     */
    public function setPath($path)
    {
        $this->path = $path;
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
     * @return NavItem
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param mixed $icon
     * @return NavItem
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAbility()
    {
        return $this->ability;
    }

    /**
     * @param mixed $ability
     * @return NavItem
     */
    public function setAbility($ability)
    {
        $this->ability = $ability;
        return $this;
    }

    public static function create() {
        return new static();
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

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param string $resource
     * @return NavItem
     */
    public function setResource(string $resource)
    {
        $this->resource = $resource;
        return $this;
    }



    public function toArray()
    {
        $arr = [
            'nav' => [
                'path' => $this->path,
                'label' => $this->label,
                'icon' => $this->icon
            ],
            'ability' => $this->ability
        ];
        if(isset($this->resource))
            $arr['resource']=$this->getResource();
        return $arr;
    }

    public function toJson($options = 0)
    {
        json_encode($this->toArray());
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
