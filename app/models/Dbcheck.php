<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2024 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\models;

use app\inc\Model;

class Dbcheck extends Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function isSchemaInstalled()
    {
        $sql = "select 1 from settings.viewer";
        $result = $this->execQuery($sql);
        $response['success'] = true;
        return $response;
    }

    public function isPostGISInstalled()
    {
        $sql = "select postgis_version()";
        $result = $this->execQuery($sql);
        $response['success'] = true;
        return $response;
    }

    public function isViewInstalled()
    {
        $sql = "select * from settings.geometry_columns_view";
        $result = $this->execQuery($sql);
        $response['success'] = true;
        return $response;
    }
}