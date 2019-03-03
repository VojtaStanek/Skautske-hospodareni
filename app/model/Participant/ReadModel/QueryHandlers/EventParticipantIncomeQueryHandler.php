<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use Model\DTO\Participant\Participant;
use Model\IParticipantServiceFactory;
use Model\ParticipantService;
use Skautis\Skautis;

class EventParticipantIncomeQueryHandler
{
    /** @var Skautis */
    private $skautis;

    /** @var ParticipantService */
    private $service;

    public function __construct(Skautis $skautis, IParticipantServiceFactory $participantFactory)
    {
        $this->skautis = $skautis;
        $this->service = $participantFactory->create('General');
    }

    public function handle(EventParticipantIncomeQuery $query) : float
    {
        $participants = $this->service->getAll($query->getEventId()->toInt());

        $participantIncome = 0.0;
        /** @var Participant $p */
        foreach ($participants as $p) {
            $participantIncome += $p->getPayment();
        }

        return $participantIncome;
    }
}
