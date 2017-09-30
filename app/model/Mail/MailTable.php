<?php

namespace Model;

use Dibi\Connection;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class MailTable extends BaseTable
{

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    public function addSmtp($unitId, $host, $username, $password, $secure): void
    {
        $this->connection->insert(self::TABLE_PA_SMTP, [
            "unitId" => $unitId,
            "host" => $host,
            "username" => $username,
            "password" => $password,
            "secure" => $secure,
            "created" => ["%sql" => "NOW()"]
        ])->execute();
    }

    public function removeSmtp($unitId, $id): void
    {
        $this->connection->query("DELETE FROM [" . self::TABLE_PA_SMTP . "] WHERE unitId=%i", $unitId, " AND id=%i", $id, " LIMIT 1");
    }

}
