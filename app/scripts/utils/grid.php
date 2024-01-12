<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2018 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

ini_set("display_errors", "On");
error_reporting(3);

use app\conf\App;
use app\conf\Connection;
use app\inc\Model;
use app\inc\Util;
use app\models\Database;
use app\models\Layer;


header("Content-type: text/plain");


include_once(__DIR__ . "/../../conf/App.php");

$db = $argv[1];
$schema = $argv[2];
$inputRel = $argv[3];
$grid = $argv[4];

$size = 30000;
$useGfs = false;
$outputTable = "grid";


new App();
Database::setDb($db);
$database = new Model();

$pl = file_get_contents(App::$param["path"] . "app/scripts/sql/st_fishnet.sql");
$database->execQuery($pl, "PG");

print_r(Connection::$param);

$database->connect();

$sql = "DROP TABLE {$schema}.{$outputTable}";
echo $sql . "\n";
$database->execQuery($sql);

$sql = "DROP TABLE {$schema}.{$grid}";
echo $sql . "\n";
$database->execQuery($sql);

$sql = "CREATE TABLE {$schema}.{$outputTable} AS SELECT st_fishnet('{$schema}.{$inputRel}','the_geom',{$size}, 25832)";
echo $sql . "\n";
$database->execQuery($sql);

$sql = "ALTER TABLE {$schema}.{$outputTable} ADD gid serial";
echo $sql . "\n";
$database->execQuery($sql);

$sql = "ALTER TABLE {$schema}.{$outputTable} ALTER st_fishnet TYPE geometry('Polygon', 25832)";
echo $sql . "\n";
$database->execQuery($sql);

$sql = "ALTER TABLE {$schema}.{$outputTable} ADD gid SERIAL";
echo $sql . "\n";
$database->execQuery($sql);

$sql = "ALTER TABLE {$schema}.{$outputTable} ADD gid SERIAL";
echo $sql . "\n";
$database->execQuery($sql);

$sql = "CREATE TABLE {$schema}.{$grid} AS SELECT $schema.grid.*
            FROM
              {$schema}.{$outputTable} LEFT JOIN
              {$schema}.{$inputRel} AS {$inputRel} ON
              st_intersects(grid.st_fishnet,{$inputRel}.the_geom)
            WHERE {$inputRel}.gid IS NOT NULL";
echo $sql . "\n";
$database->execQuery($sql);

$sql = "DROP TABLE {$schema}.{$outputTable}";
echo $sql . "\n";
$database->execQuery($sql);

die("END\n");
