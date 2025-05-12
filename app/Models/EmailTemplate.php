<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmailTemplate extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "email_template";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "is_for","name","email","message"
    ];

    /***
     * email table for credentials
     * 
     * @param $docs
     */
    function expirationTable($docs) {
        $table = '<table border="10" style="border-collapse: collapse;font-family: Arial, halvetica, sans-serif;font-size:14px;">';
        $table .="<th style='padding: 4px 8px;border: 1px solid #c6c4c4;color:rgb(238,96,85); background: #f2f2f2'>Document/License</th>";
        $table .="<th style='padding: 4px 8px;border: 1px solid #c6c4c4;color:rgb(238,96,85); background: #f2f2f2'>Expiry Date</th>";
        $table .="<th style='padding: 4px 8px;border: 1px solid #c6c4c4;color:rgb(238,96,85); background: #f2f2f2'>Remarks</th>";

        if(count($docs)) {
            foreach($docs as $doc) {
                $table .="<tr>";
                $table .="<td style='padding: 4px 8px;border: 1px solid #c6c4c4'>".$doc->name."</td>";
                $table .="<td style='padding: 4px 8px;border: 1px solid #c6c4c4'>".date('m/d/Y',strtotime($doc->exp_date))."</td>";
                $table .="<td style='padding: 4px 8px;border: 1px solid #c6c4c4'>".$doc->reminder_status." ".Carbon::parse($doc->exp_date)->diffForHumans(['parts' => 2])."</td>";
                $table .="</tr>";
            }
        }
        $table .="</table>";

        return $table;
    }
}
