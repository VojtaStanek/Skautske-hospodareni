<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Cake\Chronos\Date;
use Model\Participant\Participant;
use Money\Currency;
use Money\Money;
use function preg_match;

final class ParticipantFactory
{
    public static function create(\stdClass $skautisParticipant) : Participant
    {
        preg_match('/(?P<last>\S+)\s+(?P<first>[^(]+)(\((?P<nick>.*)\))?.*/', $skautisParticipant->Person, $matches);

        return new Participant(
            $skautisParticipant->ID,
            $matches['first'],
            $matches['last'],
            $matches['nick'] ?? null,
            $skautisParticipant->Age ?? null,
            new Date($skautisParticipant->Birthday),
            $skautisParticipant->Street,
            $skautisParticipant->City,
            (int) $skautisParticipant->Postcode,
            $skautisParticipant->State,
            isset($skautisParticipant->ID_Unit) ? (int) $skautisParticipant->ID_Unit : null,
            $skautisParticipant->Unit ?? '',
            $skautisParticipant->UnitRegistrationNumber ?? '',
            (int) $skautisParticipant->Days,
            new Money($skautisParticipant->Note ?? 0, new Currency('CZK'))
        );
    }
}
