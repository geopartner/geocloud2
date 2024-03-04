<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2024 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\models;

use app\conf\App;
use app\inc\ColorBrewer;
use app\inc\Model;
use app\inc\Util;
use PDO;
use PDOException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;


/**
 * Class Classification
 * @package app\models
 */
class Classification extends Model
{
    private string $layer;
    private Table $table;
    private array $def;
    private ?string $geometryType;
    private Tile $tile;

    /**
     * Classification constructor.
     * @param string $table
     * @throws PhpfastcacheInvalidArgumentException
     */
    function __construct(string $table)
    {
        parent::__construct();
        $this->layer = $table;
        $bits = explode(".", $this->layer);
        $this->table = new Table($bits[0] . "." . $bits[1]);
        $this->tile = new Tile($table);
        // Check if geom type is overridden
        $def = new Tile($table);
        $this->def = $def->get();
        if (($this->def['data'][0]['geotype']) && $this->def['data'][0]['geotype'] != "Default") {
            $this->geometryType = $this->def['data'][0]['geotype'];
        } else {
            $this->geometryType = null;
        }
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        $sql = "SELECT class FROM settings.geometry_columns_join WHERE _key_='$this->layer'";
        $result = $this->execQuery($sql);
        $arrNew = array();
        $response['success'] = true;
        $row = $this->fetchRow($result);
        $arr = $arr2 = !empty($row['class']) ? json_decode($row['class'], true) : [];
        for ($i = 0; $i < sizeof($arr); $i++) {
            $last = 10000;
            foreach ($arr2 as $key => $value) {
                if (isset($value->sortid) &&$value->sortid < $last) {
                    $del = $key;
                    $last = $value->sortid;
                }
            }
            if (isset($del) &&isset($arr2[$del])) {
                unset($arr2[$del]);
            }
        }
        for ($i = 0; $i < sizeof($arr); $i++) {
            $arrNew[$i] = (array)Util::casttoclass('stdClass', $arr[$i]);
            $arrNew[$i]['id'] = $i;
        }
        $response['data'] = $arrNew;
        return $response;
    }

    public function get($id): array
    {
        $classes = $this->getAll();
        $response['success'] = true;
        $arr = $classes['data'][$id];
        unset($arr['id']);
        foreach ($arr as $key => $value) {
            if ($value === null) { // Never send null to client
                $arr[$key] = "";
            }
        }
        $props = array(
            "name" => "Unnamed Class",
            "label" => false,
            "label_text" => "",
            "label2_text" => "",
            "force_label" => false,
            "color" => "#FF0000",
            "outlinecolor" => "#FF0000",
            "size" => "2",
            "width" => "1");
        foreach ($arr as $ignored) {
            foreach ($props as $key2 => $value2) {
                if (!isset($arr[$key2])) {
                    $arr[$key2] = $value2;
                }
            }
        }
        $response['data'] = array($arr);
        return $response;
    }

    /**
     * @param $class
     * @return void
     * @throws PDOException
     */
    private function store($class): void
    {
        $tableObj = new Table("settings.geometry_columns_join");
        $data['_key_'] = $this->layer;
        $data['class'] = $class;
        $tableObj->updateRecord($data, '_key_');
    }

    /**
     * @param $classWizard
     * @return void
     * @throws PDOException
     */
    private function storeWizard($classWizard): void
    {
        $tableObj = new Table("settings.geometry_columns_join");
        $data['_key_'] = $this->layer;
        $data['classwizard'] = $classWizard;
        $tableObj->updateRecord($data, "_key_");
    }

    /**
     * @return array
     * @throws PDOException
     */
    public function insert(): array
    {
        $classes = $this->getAll();
        $classes['data'][] = array("name" => "Unnamed class");
        $this->store(json_encode($classes['data'], JSON_UNESCAPED_UNICODE));
        $response['success'] = true;
        $response['message'] = "Inserted one class";
        return $response;
    }

    /**
     * @param $id
     * @param $data
     * @return array
     * @throws PDOException
     */
    public function update($id, $data): array
    {
        $classes = $this->getAll();
        foreach ((array)$data as $k => $v) {
            $classes['data'][$id][$k] = $v;
        }
        $this->store(json_encode($classes['data'], JSON_UNESCAPED_UNICODE));
        $response['success'] = true;
        $response['message'] = "Updated one class";
        return $response;
    }

    /**
     * @param $id
     * @return array
     * @throws PDOException
     */
    public function destroy($id): array // Geometry columns
    {
        $arr = [];
        $classes = $this->getAll();
        unset($classes['data'][$id]);
        foreach ($classes['data'] as $value) { // Reindex array
            unset($value['id']);
            $arr[] = $value;
        }
        $classes['data'] = $arr;
        $this->store(json_encode($classes['data'], JSON_UNESCAPED_UNICODE));
        $response['success'] = true;
        $response['message'] = "Deleted one class";
        return $response;
    }

    private function reset(): void
    {
        $this->store(json_encode(array()));
    }

    /**
     * @return void
     * @throws PDOException
     */
    private function setLayerDef(): void
    {
        $def = $this->tile->get();
        $def["data"][0]["cluster"] = null;
        $defJson = (object)$def["data"][0];
        $this->tile->update($defJson);

    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function createSingle($data, $color): array
    {
        $this->setLayerDef();
        $this->reset();
        $layer = new Layer();
        $geometryType = $this->geometryType ?: $layer->getValueFromKey($this->layer, "type");
        $this->update("0", self::createClass($geometryType, $layer->getValueFromKey($this->layer, "f_table_title") ?: $layer->getValueFromKey($this->layer, "f_table_name"), null, 10, "#" . $color, $data));
            $response['success'] = true;
            $response['message'] = "Updated one class";
        $this->storeWizard(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function createUnique($field, $data): array
    {
        $this->setLayerDef();
        $layer = new Layer();
        $geometryType = ($this->geometryType) ?: $layer->getValueFromKey($this->layer, "type");
        $fieldObj = $this->table->metaData[$field];
        $query = "SELECT distinct($field) as value FROM " . $this->table->table . " ORDER BY $field";
        $res = $this->prepare($query);
        try {
            $res->execute();
        } catch (PDOException $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $response['code'] = 400;
            return $response;
        }
        $rows = $this->fetchAll($res);
        $this->reset();
        $type = $fieldObj['type'];
        if (sizeof($rows) > 1000) {
            $response['success'] = false;
            $response['message'] = "Too many classes. Stopped after 1000.";
            $response['code'] = 405;
            return $response;
        }
        $colorBrewer = [];
        if ($data->custom->colorramp !== false && $data->custom->colorramp != "-1") {
            $colorBrewer = ColorBrewer::getQualitative($data->custom->colorramp);
        }
        $cArr = array();
        $expression = '';
        foreach ($rows as $key => $row) {
            if ($type == "number" || $type == "int") {
                $expression = "[$field]={$row['value']}";
            }
            if ($type == "text" || $type == "string") {
                $expression = "'[$field]'='{$row['value']}'";
            }
            $name = $row['value'];
            if ($data->custom->colorramp !== false && $data->custom->colorramp != "-1") {
                $c = current($colorBrewer);
                next($colorBrewer);
            } else {
                $c = null;
            }
            $cArr[$key] = self::createClass($geometryType, $name, $expression, ($key * 10) + 10, $c, $data);
        }
        $this->store(json_encode($cArr, JSON_UNESCAPED_UNICODE));
        $response['success'] = true;
        $response['message'] = "Updated " . sizeof($rows) . " classes";
        $this->storeWizard(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response;
    }

    /**
     * @param $field
     * @param $num
     * @param $startColor
     * @param $endColor
     * @param $data
     * @return array
     * @throws PhpfastcacheInvalidArgumentException|PDOException
     */
    public function createEqualIntervals($field, $num, $startColor, $endColor, $data): array
    {
        $this->setLayerDef();
        $layer = new Layer();
        $geometryType = ($this->geometryType) ?: $layer->getValueFromKey($this->layer, "type");
        if ($geometryType == "RASTER") {
            $parts = explode(".", $this->layer);
            $setSchema = "set search_path to public,$parts[0]";
            $res = $this->prepare($setSchema);
            $res->execute();
            $query = "SELECT band, (stats).min, (stats).max FROM (SELECT band, public.ST_SummaryStats('$parts[1]','rast', band) As stats FROM generate_series(1,1) As band) As foo;";
        } else {
            $query = "SELECT max($field) as max, min($field) FROM {$this->table->table}";
        }
        $res = $this->prepare($query);
        $res->execute();
        $row = $this->fetchRow($res);
        $diff = $row["max"] - $row["min"];
        $interval = $diff / $num;
        $this->reset();

        $grad = Util::makeGradient($startColor, $endColor, $num);
        for ($i = 1; $i <= ($num); $i++) {
            $top = $row['min'] + ($interval * $i);
            $bottom = $top - $interval;
            if ($i == $num) {
                $expression = "[$field]>=" . $bottom . " AND [$field]<=" . $top;
            } else {
                $expression = "[$field]>=" . $bottom . " AND [$field]<" . $top;
            }
            $name = " < " . round(($top), 2);
            $class = self::createClass($geometryType, $name, $expression, ((($i - 1) * 10) + 10), $grad[$i - 1], $data);
            $this->update(($i - 1), $class);
        }
        $response['success'] = true;
        $response['message'] = "Updated " . $num . " classes";
        $this->storeWizard(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function createQuantile($field, $num, $startColor, $endColor, $data, $update = true): array
    {
        $this->setLayerDef();
        $layer = new Layer();
        $geometryType = $layer->getValueFromKey($this->layer, "type");
        $query = "SELECT count(*) AS count FROM " . $this->table->table;
        $res = $this->prepare($query);
        $res->execute();
        $row = $this->fetchRow($res);
        $count = $row["count"];
        $numPerClass = $temp = ($count / $num);
        $query = "SELECT * FROM " . $this->table->table . " ORDER BY $field";
        $res = $this->prepare($query);
        $res->execute();
        $this->reset();
        $grad = Util::makeGradient($startColor, $endColor, $num);
        $bottom = 0;
        $top = 0;
        $tops = [];
        $u = 0;
        for ($i = 1; $i <= $count; $i++) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            if ($i == 1) {
                $bottom = $row[$field] ?? 0;
            }
            if ($i >= $temp || $i == $count) {
                if ($top) {
                    $bottom = $top;
                }
                $top = $row[$field] ?? 0;
                if ($i == $count) {
                    $expression = "[$field]>=" . $bottom . " AND [$field]<=" . $top;
                } else {
                    $expression = "[$field]>=" . $bottom . " AND [$field]<" . $top;
                }
                $name = " < " . round(($top), 2);
                $tops[] = array($top, $grad[$u]);
                if ($update) {
                    $class = self::createClass($geometryType, $name, $expression, (($u + 1) * 10), $grad[$u], $data);
                    $this->update($u, $class);
                }
                $u++;
                $temp = $temp + $numPerClass;
            }
        }
        $response['success'] = true;
        $response['values'] = $tops;
        $response['message'] = "Updated " . $num . " classes";
        $this->storeWizard(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function createCluster($distance, $data): array
    {
        $layer = new Layer();
        $geometryType = ($this->geometryType) ?: $layer->getValueFromKey($this->layer, "type");
        if ($geometryType != "POINT" && $geometryType != "MULTIPOINT") {
            $response['success'] = false;
            $response['message'] = "Only point layers can be clustered";
            $response['code'] = 400;
            return $response;
        }
        $this->reset();

        // Set layer def
        $def = $this->tile->get();
        $def["data"][0]["cluster"] = $distance;
        $def["data"][0]["meta_tiles"] = true;
        $def["data"][0]["meta_size"] = 4;
        $defJson = (object)$def["data"][0];
        $this->tile->update($defJson);
        //Set single class
        if (App::$param["mapserver_ver_7"]) {
            $ClusterFeatureCount = "Cluster_FeatureCount";
        } else {
            $ClusterFeatureCount = "Cluster:FeatureCount";
        }
        $expression = "[$ClusterFeatureCount]=1";
        $name = "Single";
        $this->update(0, self::createClass($geometryType, $name, $expression, 10, "#0000FF", $data));
        //Set cluster class
        $expression = "[$ClusterFeatureCount]>1";
        $name = "Cluster";
        $data->labelText = "[$ClusterFeatureCount]";
        $data->labelSize = "9";
        $data->labelPosition = "cc";
        $data->symbolSize = "50";
        $data->overlaySize = "35";
        $data->overlayColor = "#00FF00";
        $data->overlaySymbol = "circle";
        $data->symbol = "circle";
        $data->opacity = "25";
        $data->overlayOpacity = "70";
        $data->force = true;

        $this->update(1, self::createClass($geometryType, $name, $expression, 20, "#00FF00", $data));
        $response['success'] = true;
        $response['message'] = "Updated 2 classes";
        $this->storeWizard(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response;
    }

    public function copyClasses($to, $from): array
    {
        $query = "SELECT class FROM settings.geometry_columns_join WHERE _key_ =:from";
        $res = $this->prepare($query);
        $res->execute(array("from" => $from));
        $row = $this->fetchRow($res);
        $data['class'] = $row["class"];
        $data['_key_'] = $to;
        $geometryColumnsObj = new table("settings.geometry_columns_join");
        return $geometryColumnsObj->updateRecord($data, "_key_");
    }


    static function createClass($type, $name = "Unnamed class", $expression = null, $sortid = 1, $color = null, $data = null): object
    {
        $symbol = ($data->symbol) ?: "";
        $size = ($data->symbolSize) ?: "";
        $outlineColor = ($data->outlineColor) ?: "";
        $color = ($color) ?: Util::randHexColor();
        if ($type == "POINT" || $type == "MULTIPOINT") {
            $symbol = ($data->symbol) ?: "circle";
            $size = ($data->symbolSize) ?: 10;
        }
        return (object)array(
            "sortid" => $sortid,
            "name" => $name,
            "expression" => $expression,
            "label" => !empty($data->labelText),
            "label_size" => !empty($data->labelSize) ? $data->labelSize : "",
            "label_color" => !empty($data->labelColor) ? $data->labelColor : "",
            "color" => $color,
            "outlinecolor" => !empty($outlineColor) ? $outlineColor : "",
            "symbol" => $symbol,
            "angle" => !empty($data->angle) ? $data->angle : "",
            "size" => $size,
            "width" => !empty($data->lineWidth) ? $data->lineWidth : "",
            "overlaycolor" => !empty($data->overlayColor) ? $data->overlayColor : "",
            "overlayoutlinecolor" => "",
            "overlaysymbol" => !empty($data->overlaySymbol) ? $data->overlaySymbol : "",
            "overlaysize" => !empty($data->overlaySize) ? $data->overlaySize : "",
            "overlaywidth" => "",
            "label_text" => !empty($data->labelText) ? $data->labelText : "",
            "label_position" => !empty($data->labelPosition) ? $data->labelPosition : "",
            "label_font" => !empty($data->labelFont) ? $data->labelFont : "",
            "label_fontweight" => !empty($data->labelFontWeight) ? $data->labelFontWeight : "",
            "label_angle" => !empty($data->labelAngle) ? $data->labelAngle : "",
            "label_backgroundcolor" => !empty($data->labelBackgroundcolor) ? $data->labelBackgroundcolor : "",
            "style_opacity" => !empty($data->opacity) ? $data->opacity : "",
            "overlaystyle_opacity" => !empty($data->overlayOpacity) ? $data->overlayOpacity : "",
            "label_force" => !empty($data->force) ? $data->force : "",
        );
    }
}