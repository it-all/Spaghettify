<?php

require __DIR__."/../src/init.php";
$res = pg_query("SELECT * FROM administrators");
echo "Administrators\n";
while ($row = pg_fetch_assoc($res)) {
    echo "administrator ".$row['id']. ": ".$row['name']."\n";
}
