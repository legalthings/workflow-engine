<?php

use Jasny\DB\Entity;

/**
 * Basic entity the extends stdClass
 */
class BasicEntity extends stdClass implements Entity
{
    use Entity\Implementation;
}
