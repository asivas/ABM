<?php

namespace Asivas\ABM\Form;

class RuleRequired extends Rule
{

    /**
     * @param $value
     * @return bool
     */
    public function validate($value): bool
    {
        return isset($value);
    }
}
