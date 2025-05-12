<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Http\Traits\Utility;

class Documents extends Model
{
    use HasFactory,Utility;

    /**
     * check the users's document expirey
     * 
     * 
     */
    public function checkDocumentsExpiration() {
       
        return DB::table("user_licenses as cul")
        
        ->select("cul.user_id",DB::raw("COUNT(cm_cul.id) as total"))
        
        ->join("license_types as lt",function($join) {
            $join->on('lt.id','=','cul.type_id')
            ->where('lt.versioning_type','=','number');
        })
        
        ->whereRaw("cm_cul.document_version = (SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = cm_cul.type_id
                                       AND ul.user_id = cm_cul.user_id AND ul.license_no = cm_cul.license_no) AND
                                       ((CURDATE() > cm_cul.exp_date) OR (CURDATE() < cm_cul.exp_date AND cm_cul.notify_before_exp > 0 AND DATE_SUB(cm_cul.exp_date, INTERVAL cm_cul.notify_before_exp DAY) = CURDATE()))                   
        ")
        
        ->groupBy('cul.user_id')
        
        ->get();
    }
    /**
     * user document details
     * 
     * @param $userId
     */
    public function userDocumentDetails($userId) {
        return DB::table("user_licenses as cul")
        
        ->select("cul.id",DB::raw("
            (
            CASE WHEN (CURDATE() > cm_cul.exp_date)
                THEN 'Expired'
            WHEN (CURDATE() < cm_cul.exp_date AND cm_cul.notify_before_exp >0 AND DATE_SUB(cm_cul.exp_date, INTERVAL cm_cul.notify_before_exp DAY) = CURDATE())
                THEN 'Expiring'
            ELSE '-' END) AS reminder_status
        "),"cul.exp_date","cul.notify_before_exp","cul.license_no","lt.name",
        DB::raw("(SELECT email FROM cm_users WHERE id= cm_cul.user_id) AS user_email"),
        DB::raw("(SELECT CONCAT(first_name,' ', last_name) FROM cm_users WHERE id= cm_cul.user_id) AS user_name")
        )
        
        ->join("license_types as lt",function($join) {
            $join->on('lt.id','=','cul.type_id')
            ->where('lt.versioning_type','=','number');
        })
        
        ->whereRaw("cm_cul.document_version = (SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = cm_cul.type_id
                                       AND ul.user_id = $userId AND ul.license_no = cm_cul.license_no) AND
                                       ((CURDATE() > cm_cul.exp_date) OR (CURDATE() < cm_cul.exp_date AND cm_cul.notify_before_exp > 0 AND DATE_SUB(cm_cul.exp_date, INTERVAL cm_cul.notify_before_exp DAY) = CURDATE()))                   
        ")
        
        ->get();
    }
}
