<?php

require __DIR__."/../src/init.php";
$res = pg_query("SELECT * FROM administrators");
echo "Administrators".PHP_EOL;
while ($row = pg_fetch_assoc($res)) {
    echo "administrator ".$row['id']. ": ".$row['name'].PHP_EOL;
}
