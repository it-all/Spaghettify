Roles

roles.level has a unique constraint in db

inserting role: level can only be the next incremental level (current max level + 1)

updating level: causes update of all levels necessary to ensure levels are 1 - # roles.