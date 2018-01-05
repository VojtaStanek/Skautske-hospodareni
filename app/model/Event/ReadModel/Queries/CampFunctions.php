<?php

namespace Model\Event\ReadModel\Queries;

use Model\Event\ReadModel\QueryHandlers\CampFunctionsHandler;
use Model\Event\SkautisCampId;

/**
 * @see CampFunctionsHandler
 */
class CampFunctions
{

    /** @var SkautisCampId */
    private $campId;

    public function __construct(SkautisCampId $campId)
    {
        $this->campId = $campId;
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }

}
