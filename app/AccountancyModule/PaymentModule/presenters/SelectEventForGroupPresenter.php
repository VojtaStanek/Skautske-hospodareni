<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use Cake\Chronos\Date;
use Model\Payment\ReadModel\Queries\EventsWithoutGroupQuery;

use function array_values;

final class SelectEventForGroupPresenter extends BasePresenter
{
    public function renderDefault(): void
    {
        $this->template->setParameters(
            ['events' => array_values($this->queryBus->handle(new EventsWithoutGroupQuery(Date::today()->year)))],
        );
    }
}