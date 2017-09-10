<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\SystemEvents;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;

class SystemEventsModel extends DatabaseTableModel
{
    public function __construct()
    {
        parent::__construct('system_events', 'created', false);
    }

    public function insertEvent(string $title, string $eventType = 'info', int $adminId = null, string $notes = null)
    {
        if (!$eventTypeId = $this->getEventTypeId($eventType)) {
            throw new \Exception("Invalid eventType: $eventType");
        }

        $this->insert($title, (int) $eventTypeId, $notes, $adminId);
    }

    public function getEventTypeId(string $eventType)
    {
        $q = new QueryBuilder("SELECT id FROM system_event_types WHERE event_type = $1", $eventType);
        return $q->getOne();
    }

    public function insert(string $title, int $eventType = 2, string $notes = null, int $adminId = null)
    {
        if (strlen(trim($title)) == 0) {
            throw new \Exception("Title is blank");
        }
        if ($notes !== null && strlen(trim($notes)) == 0) {
            $notes = null;
        }
        // query can fail if event_type or admin_id fk not present.

        $q = new QueryBuilder("INSERT INTO system_events (event_type, title, notes, admin_id) VALUES($1, $2, $3, $4)", $eventType, $title, $notes, $adminId);
        return $q->execute();
    }

    public function getView()
    {
        $q = new QueryBuilder("SELECT se.id, syet.event_type as type, se.title as event, se.notes, admins.name as admin, se.created as time_stamp FROM system_events se JOIN system_event_types syet ON se.event_type = syet.id LEFT OUTER JOIN admins ON se.admin_id = admins.id ORDER BY se.created DESC");
        return $q->execute();
    }
}
