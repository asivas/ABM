<?php

namespace ABM\Form;

 abstract class Rule
{
    protected $errorMessage;

    public  function __construct($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }
     static function create($errorMessage) {
         return new static($errorMessage);
     }

     /**
      * @return mixed
      */
     public function getErrorMessage()
     {
         return $this->errorMessage;
     }

     /**
      * @param mixed $errorMessage
      */
     public function setErrorMessage($errorMessage): void
     {
         $this->errorMessage = $errorMessage;
     }


    public abstract function validate($value);

}
