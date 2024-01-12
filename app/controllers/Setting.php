<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2018 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\controllers;

use app\inc\Controller;
use app\inc\Input;

class Setting extends Controller
{
    private $settings;

    function __construct()
    {
        parent::__construct();

        $this->settings = new \app\models\Setting();
    }

    public function get_index()
    {
        return $this->settings->get();
    }

    public function put_index()
    {
        return $this->settings->update(Input::get());
    }

    public function put_pw()
    {
        return $this->settings->updatePw(Input::get('pw'));
    }

    public function put_apikey()
    {
        return $this->settings->updateApiKey();
    }

    public function put_extent()
    {
        $response = $this->auth(null, array());
        return (!$response['success']) ? $response : $this->settings->updateExtent(json_decode(Input::get())->data);
    }

    public function put_extentrestrict()
    {
        $response = $this->auth(null, array(), true); // Never sub-user
        return (!$response['success']) ? $response : $this->settings->updateExtentRestrict(json_decode(Input::get())->data);
    }
    public function get_usergroups() // Will update - used with jsonp
    {
        return $this->settings->updateUserGroups(json_decode(Input::get("q"))->data);
    }
}
