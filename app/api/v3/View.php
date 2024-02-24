<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2024 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\api\v3;

use app\inc\Controller;
use app\inc\Input;
use app\inc\Model;
use app\inc\Route;


/**
 * Class Grid
 * @package app\api\v3
 */
class View extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return array
     *
     * @OA\Post(
     *   path="/api/v3/view",
     *   tags={"Grid"},
     *   summary="Create a fishnet grid from an input polygon",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     description="Fishnet parameters",
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         @OA\Property(property="table", type="string", example="new_grid"),
     *         @OA\Property(property="extent", type="string", example="my_extent_polygon"),
     *         @OA\Property(property="size", type="integer", example=10000),
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="200",
     *     description="Return true if fishnet grid was created",
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         @OA\Property(property="success", type="boolean", example=true)
     *       )
     *     )
     *   )
     * )
     */
    public function post_index(): array
    {
        $model = new Model();
        $body = Input::getBody();
        $arr = json_decode($body, true);

        $schema = $arr['schema'];
        $model->storeViewsFromSchema($schema);

        return ["code" => "204"];

    }

    public function get_index(): array
    {
        $model = new Model();
        $schema = Route::$params['schema'];
        return ['views' => $model->getStarViewsFromStore($schema)];
    }

    public function put_index(): array
    {
        $model = new Model();
        $schema = Route::$params['schema'];
        $target = Route::$params['target'];
        $model->createStarViewsFromStore($schema, $target);
        return ["code" => "204"];
    }
}
