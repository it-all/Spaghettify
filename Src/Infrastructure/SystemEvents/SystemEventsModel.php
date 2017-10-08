<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\SystemEvents;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\SelectBuilder;

class SystemEventsModel extends DatabaseTableModel
{
    // event types: debug, info, notice, warning, error, critical, alert, emergency [props to monolog]
    const COLUMNS = [
        'id' => 'se.id',
        'time_stamp' => 'time_stamp',
        'type' => 'type',
        'event' => 'event',
        'admin' => 'admin',
        'notes' => 'se.notes',
        'ip_address' => 'se.ip_address',
        'request_method' => 'se.request_method',
        'resource' => 'se.resource'
    ];

    public function __construct()
    {
        // note time_stamp is the alias for created used in view query
        parent::__construct('system_events', 'time_stamp', false);
    }

    public function insertDebug(string $title, int $adminId = null, string $notes = null)
    {
        $this->insertEvent($title, 'debug', $adminId, $notes);
    }

    public function insertInfo(string $title, int $adminId = null, string $notes = null)
    {
        $this->insertEvent($title, 'info', $adminId, $notes);
    }

    public function insertNotice(string $title, int $adminId = null, string $notes = null)
    {
        $this->insertEvent($title, 'notice', $adminId, $notes);
    }

    public function insertWarning(string $title, int $adminId = null, string $notes = null)
    {
        $this->insertEvent($title, 'warning', $adminId, $notes);
    }

    public function insertError(string $title, int $adminId = null, string $notes = null)
    {
        $this->insertEvent($title, 'error', $adminId, $notes);
    }

    public function insertCritical(string $title, int $adminId = null, string $notes = null)
    {
        $this->insertEvent($title, 'critical', $adminId, $notes);
    }

    public function insertAlert(string $title, int $adminId = null, string $notes = null)
    {
        $this->insertEvent($title, 'alert', $adminId, $notes);
    }

    public function insertEmergency(string $title, int $adminId = null, string $notes = null)
    {
        $this->insertEvent($title, 'emergency', $adminId, $notes);
    }

    public function insertEvent(string $title, string $eventType = 'info', int $adminId = null, string $notes = null)
    {
        if (!$eventTypeId = $this->getEventTypeId($eventType)) {
            throw new \Exception("Invalid eventType: $eventType");
        }

        $this->insert($title, (int) $eventTypeId, $notes, $adminId);
    }

    private function insert(string $title, int $eventType = 2, string $notes = null, int $adminId = null)
    {
        if (strlen(trim($title)) == 0) {
            throw new \Exception("Title cannot be blank");
        }

        if ($notes !== null && strlen(trim($notes)) == 0) {
            $notes = null;
        }

        // allow 0 to be passed in instead of null, convert to null so query won't fail
        if ($adminId == 0) {
            $adminId = null;
        }

        // query can fail if event_type or admin_id fk not present.

        $q = new QueryBuilder("INSERT INTO system_events (event_type, title, notes, admin_id, ip_address, resource, request_method) VALUES($1, $2, $3, $4, $5, $6, $7)", $eventType, $title, $notes, $adminId, $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

        try {
            $res = $q->execute();
        } catch (\Exception $e) {
            // suppress exception as it will result in infinite loop in error handler, which also calls this fn
            return false;
        }

        return $res;
    }

    public function getEventTypeId(string $eventType)
    {
        $q = new QueryBuilder("SELECT id FROM system_event_types WHERE event_type = $1", $eventType);
        return $q->getOne();
    }

    public static function getColumnNameSqlForColumnName(string $columnName): string
    {
        if (isset(self::COLUMNS[strtolower($columnName)])) {
            return self::COLUMNS[strtolower($columnName)];
        }
        throw new \Exception("undefined column $columnName");
    }

    public function getView(array $whereColumnsInfo = null)
    {
        $selectClause = "SELECT se.id, se.created as time_stamp, se.event_type as type, se.title as event, admins.name as admin, se.notes, se.ip_address, se.request_method as method, se.resource";

        $fromClause = "FROM system_events se JOIN system_event_types syet ON se.event_type = syet.id LEFT OUTER JOIN admins ON se.admin_id = admins.id";

        $orderByClause = "ORDER BY se.created DESC";

        if ($whereColumnsInfo != null) {
            $this->validateWhereColumns($whereColumnsInfo);
        }

        $q = new SelectBuilder($selectClause, $fromClause, $whereColumnsInfo, $orderByClause);
        return $q->execute();
    }

    // make sure each columnNameSql in columns
    private function validateWhereColumns(array $whereColumnsInfo)
    {
        foreach ($whereColumnsInfo as $columnNameSql => $columnWhereInfo) {
            if (!in_array($columnNameSql, self::COLUMNS)) {
                throw new \Exception("Invalid where column $columnNameSql");
            }
        }
    }
}
