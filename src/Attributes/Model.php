<?php

namespace Nawado\Myorm\Attributes;

use Attribute;

#[Attribute]
class Model
{
    public string $className;
    public function __construct(string $className)
    {
        $this->className = $className;
    }
}
