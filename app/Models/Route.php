<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\Utility;
class Route extends Model
{
    use HasFactory,Utility;
    
       /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "routes";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "parent_route_id",
        "parent_navigation_id",
        "name",
        "component",
        "routes",
        "icon",
        "is_navigation",
        "sort_by",
    ];
    /**
     * report routes
     */
    function reportRoutes() {
        $sql = "SELECT * FROM `cm_routes`
        WHERE parent_navigation_id = (SELECT id FROM cm_routes WHERE name = 'Reports')
        ORDER BY sort_by";

        return $this->rawQuery($sql);

    }
     /**
     * report routes
     */
    function securityRoutes() {
        $sql = "SELECT * FROM `cm_routes`
        WHERE parent_navigation_id = (SELECT id FROM cm_routes WHERE name = 'Security')
        ORDER BY sort_by";

        return $this->rawQuery($sql);

    }
    /**
     * report routes
     */
    function leadRoutes() {
        $sql = "SELECT * FROM `cm_routes`
        WHERE parent_navigation_id in (SELECT id FROM cm_routes WHERE name = 'lead')
        ORDER BY sort_by";

        return $this->rawQuery($sql);

    }
    /**
     * settings routes
     */
    function settingRoutes() {
        $sql = "SELECT * FROM `cm_routes`
        WHERE parent_navigation_id = (SELECT id FROM cm_routes WHERE name = 'Settings')
        ORDER BY sort_by";

        return $this->rawQuery($sql);

    }
     /**
     * settings routes
     */
    function settingSection($sectionId) {
        $sql = "SELECT * FROM `cm_routes`
        WHERE parent_navigation_id = $sectionId
        ORDER BY sort_by";

        return $this->rawQuery($sql);

    }
    /**
     * report headers
     */
    function reportHeader() {
        $sql = "SELECT id, name, component, routes
        FROM `cm_routes`
        WHERE parent_navigation_id = (SELECT id
                                   FROM cm_routes
                                   WHERE name = 'REPORTS' AND parent_navigation_id = '0'
                                   AND is_navigation = '1')";

        return $this->rawQuery($sql);
    }
    /**
     * report innder headers
     * 
     * @param $id
     */
    function reportInnderHeader($id) {

        $sql = "SELECT id, name, component,routes 
        FROM `cm_routes`
        WHERE parent_navigation_id = '$id'
        ORDER BY sort_by";
        return $this->rawQuery($sql);
    }
}
