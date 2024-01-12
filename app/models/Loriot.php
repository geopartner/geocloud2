<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2018 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\models;

use app\inc\Model;
use PDOException;

class Loriot extends Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function insert($data, $table)
    {
        $data = array_change_key_case($data, CASE_LOWER);

        $defaults = [
            "cmd" => null,
            "eui" => null,
            "ts" => null,
            "ack" => false,
            "fcnt" => null,
            "port" => null,
            "encdata" => null,
            "data" => null,
        ];

        foreach ($defaults as $key => $value) {
            $defaults[$key] = $data[$key];
        }

        $defaults["ack"] = $defaults["ack"] ? "t" : "f";

        $response = array();
        $sql = "INSERT INTO {$table}(cmd, eui, ts, ack, fcnt, port, encdata, data) VALUES (:cmd, :eui, to_timestamp(:ts), :ack, :fcnt, :port, :encdata, :data) RETURNING *";
        $res = $this->prepare($sql);

        try {
            $res->execute($defaults);
        } catch (PDOException $e) {
            $response["success"] = false;
            $response["message"] = $e->getMessage();
            $response["code"] = "401";
            return $response;


        }

        $response["success"] = true;
        $response["data"] = json_encode($defaults);
        return $response;
    }
}