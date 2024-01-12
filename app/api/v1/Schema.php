<?php
/**
 * Long description for file
 *
 * Long description for file (if any)...
 *
 * @category   API
 * @package    app\api\v1
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2018 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 * @since      File available since Release 2013.1
 *
 */

namespace app\api\v1;

use app\inc\Controller;
use app\models\Database;

/**
 * Class Schema
 * @package app\api\v1
 */
class Schema extends Controller
{
    /**
     * Schema constructor.
     */
    function __construct()
    {
        parent::__construct();

        $this->db = new Database();
    }

    /**
     * @return mixed
     */
    public function get_index()
    {
        return $this->db->listAllSchemas();
    }
}