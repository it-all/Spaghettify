<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\SystemEvents;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;

class SystemEventsModel extends DatabaseTableModel
{
    public function __construct()
    {
        // note time_stamp is the alias for created used in view query
        parent::__construct('system_events', 'time_stamp', false);
    }

    public function insertInfo(string $title, int $adminId = null, string $notes = null)
    {
        $this->insertEvent($title, 'info', $adminId, $notes);
    }

    public function insertWarning(string $title, int $adminId = null, string $notes = null)
    {
        $this->insertEvent($title, 'warning', $adminId, $notes);
    }

    public function insertEvent(string $title, string $eventType = 'info', int $adminId = null, string $notes = null, bool $silent = false)
    {
        if (!$eventTypeId = $this->getEventTypeId($eventType)) {
            throw new \Exception("Invalid eventType: $eventType");
        }

        $this->insert($title, (int) $eventTypeId, $notes, $adminId, $silent);
    }

    private function insert(string $title, int $eventType = 2, string $notes = null, int $adminId = null, bool $silent = false)
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

    public function getView()
    {
        $q = new QueryBuilder("SELECT se.id, syet.event_type as type, se.title as event, se.notes, admins.name as admin, se.created as time_stamp FROM system_events se JOIN system_event_types syet ON se.event_type = syet.id LEFT OUTER JOIN admins ON se.admin_id = admins.id ORDER BY se.created DESC");
        return $q->execute();
    }
}
