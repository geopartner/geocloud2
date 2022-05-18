<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2021 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\models;

use app\inc\Model;
use app\inc\UserFilter;
use PDOException;
use Generator;


/**
 * Class Geofencing
 * @package app\models
 */
class Geofence extends Model
{
    /**
     * @var UserFilter
     */
    private $userFilter;

    public const ALLOW_ACCESS = "allow";
    public const DENY_ACCESS = "deny";
    public const LIMIT_ACCESS = "limit";

    /**
     * Geofencing constructor.
     * @param UserFilter $userFilter
     */
    public function __construct(UserFilter $userFilter)
    {
        parent::__construct();
        $this->userFilter = $userFilter;
    }

    /**
     * @return array<mixed>
     */
    public function authorize(array $rules): array
    {
        $filters = [];
        $response = [];
        foreach ($rules as $rule) {
            if (
                ($this->userFilter->userName == $rule["username"] || $rule["username"] == "*") &&
                ($this->userFilter->layer == $rule["layer"] || $rule["layer"] == "*") &&
                ($this->userFilter->service == $rule["service"] || $rule["service"] == "*") &&
                ($this->userFilter->ipAddress == $rule["ipaddress"] || $rule["ipaddress"] == "*") &&
                ($this->userFilter->request == $rule["request"] || $rule["request"] == "*")
            ) {
                if ($rule["access"] == self::LIMIT_ACCESS) {
                    $filters["read"] = $rule["read_filter"];
                    $filters["write"] = $rule["write_filter"];
                    $filters["read_spatial"] = $rule["read_spatial_filter"];
                    $filters["write_spatial"] = $rule["write_spatial_filter"];
                }
                $response["access"] = $rule["access"];
                break;
            }
        }
        $response["filters"] = $filters;
        $response["success"] = true;
        return $response;
    }

    /**
     * @return array
     */
    public function getRules(): array
    {

            $sql = "SELECT * FROM settings.geofence order by priority";
            $res = $this->prepare($sql);
            $res->execute();
            $arr = [];
            while ($row = $this->fetchRow($res)) {
                $arr[] = $row;
            }
        return $arr;
    }
}