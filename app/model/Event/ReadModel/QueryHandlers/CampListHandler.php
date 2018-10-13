<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\Camp;
use Model\Event\ReadModel\Queries\CampList;
use Model\Skautis\Factory\ICampFactory;
use Skautis\Skautis;
use function array_combine;
use function array_map;
use function is_object;

class CampListHandler
{
    /** @var Skautis */
    private $skautis;

    /** @var ICampFactory */
    private $campFactory;

    public function __construct(Skautis $skautis, ICampFactory $campFactory)
    {
        $this->skautis     = $skautis;
        $this->campFactory = $campFactory;
    }

    /**
     * @return Camp[]
     */
    public function handle(CampList $query) : array
    {
        $camps = $this->skautis->event->EventCampAll([
            'Year' => $query->getYear(),
        ]);

        if (is_object($camps)) {
            return [];
        }
        $camps = array_map([$this->campFactory, 'create'], $camps); //It changes ID to localIDs
        return array_combine(
            array_map(function (Camp $u) : int {
                return $u->getId();
            }, $camps),
            $camps
        );
    }
}
