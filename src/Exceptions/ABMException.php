<?php

namespace Asivas\ABM\Exceptions;

class ABMException extends \Exception
{
    public function getStatusCode()
    {
        if ($this->getPrevious()) {
            return $this->getPrevious()->getCode();
        } else {
            return $this->code ?? 500;
        }
    }
}
