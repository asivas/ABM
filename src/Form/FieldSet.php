<?php

namespace Asivas\ABM\Form;

class FieldSet  extends FieldGroup
{

    protected $fieldGroups;

    /**
     * @return mixed
     */
    public function getFieldGroups()
    {
        return $this->fieldGroups;
    }

    /**
     * @param mixed $fieldGroups
     * @return FieldSet
     */
    public function setFieldGroups($fieldGroups)
    {
        $this->fieldGroups = $fieldGroups;
        return $this;
    }

    public function toArray()
    {
        $array =  parent::toArray();
        $groups = [];
        /** @var FieldGroup $fieldGroup */
        foreach ($this->fieldGroups as $fieldGroup) {
            $groups[$fieldGroup->getName()] = $fieldGroup->toArray();
        }
        $array['groups'] = $groups;
        return $array;
    }

    public function addFieldGroup(FieldGroup $field) {
        $this->fields[] = $field;
    }

    public function insertFieldGroup(FieldGroup $field, $position) {
        $this->fields[$position] = $field;
    }

    /**
     * @param string $fieldGroupName
     * @return bool
     */
    public function removeFieldGroup($fieldGroupName) {
        /**
         * @var  $pos
         * @var FieldGroup $group
         */
        foreach ($this->fieldGroups as $pos => $group)
        {
            if($group->getName()===$fieldGroupName) {
                unset($this->fieldGroups[$pos]);
                return true;
            }
        }
        return false;
    }

}