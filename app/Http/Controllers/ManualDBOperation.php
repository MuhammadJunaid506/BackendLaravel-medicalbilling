<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;



class ManualDBOperation extends Controller
{

    //

    /**
     *fetch the user license types
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     **/
    public function updateBillingCliams(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        //dd($request->file());
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        // Read the CSV file content into a variable
        $csvContents = file_get_contents($request->file('csv_file')->getRealPath());

        // Parse the CSV data into an array
        $csvData = str_getcsv($csvContents, "\n");

        $csvArray = [];
        foreach ($csvData as $csvRow) {
            $csvArray[] = str_getcsv($csvRow, ",");
        }

        $havingErrorClaims = array();
        $statusRemarks = array();
        $rows_affected = 0;
        // $this->printR($csvArray,true);
        $rows = 0;
        foreach ($csvArray as $key => $item) {
            if ($key == 0)
                continue;

            // $this->printR($item,true);
            // $modifiedValue = str_replace("$", "", $item['3']);
            //dd($item['0'],floatval($modifiedValue));
            //dd($csvArray);
            $claimNo      = $item['2'];
            $dos       = $item['10'];
            $updateData = [
                'dos' => date('Y-m-d', strtotime($dos))

            ];
            // echo $claimNo . " " . $dos . " " . date('Y-m-d', strtotime($dos)) . "\n";
            // $this->printR($updateData,true);
            $rows = DB::table('billing')->where('claim_no', $claimNo)
            ->update($updateData);
            if(DB::table('account_receivable')->where('claim_no', $claimNo)->count() > 0) {
                DB::table('account_receivable')->where('claim_no', $claimNo)
                ->update($updateData);
            }
            
            $rows_affected += $rows;

           
        }

        return response(["totall_effected" => $rows_affected, "total" => count($csvArray) - 1]);
    }
    
    public function updateARCliams(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        //dd($request->file());
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        // Read the CSV file content into a variable
        $csvContents = file_get_contents($request->file('csv_file')->getRealPath());

        // Parse the CSV data into an array
        $csvData = str_getcsv($csvContents, "\n");

        $csvArray = [];
        foreach ($csvData as $csvRow) {
            $csvArray[] = str_getcsv($csvRow, ",");
        }

        $havingErrorClaims = array();
        $statusRemarks = array();
        $rows_affected = 0;
        // $this->printR($csvArray,true);
        foreach ($csvArray as $key => $item) {
            if ($key == 0)
                continue;

            // $this->printR($item,true);
            // $modifiedValue = str_replace("$", "", $item['3']);
            //dd($item['0'],floatval($modifiedValue));
            //dd($csvArray);
            $balanceAmount      = $item['7'];
            $billedAmount       = $item['4'];
            $paidAmount         = $item['5'];
            $adjustmentAmount   = $item['6'];
            $status             = trim($item['8']);
            $remarks            = trim($item['9']);
            // if($item['0'] == "65767")
            //     $this->printR($item,true);

            $remarksData = DB::table('revenue_cycle_remarks')->where("remarks","=",$remarks)->first("id");
            $statusData  = DB::table('revenue_cycle_status')->where("status","=",$status)->first("id");
            // dd($statusData);
            // echo $status . PHP_EOL;
            // echo $remarks . PHP_EOL;
            // echo $item['0'] . PHP_EOL;
            if(isset($statusData->id) && isset($remarksData->id)) {
                $updateData = [
                    'balance_amount' => $balanceAmount,'adjustment_amount' => $adjustmentAmount,
                    'billed_amount' => $billedAmount,'paid_amount' => $paidAmount,'status' => $statusData->id,
                    'remarks' => $remarksData->id

                ];
                // dd($updateData);
                $rows = DB::table('account_receivable')->where('id', $item['0'])
                    ->update($updateData);
                    
                $rows_affected += $rows;
            }
            else {
                $item['0'] = filter_var($item['0'], FILTER_VALIDATE_INT);
            
                $havingError = [$item['0'],$status,$remarks];
                array_push($havingErrorClaims,$item);
                array_push($statusRemarks,$havingError);
                
            }

           
        }

        return response(["totall_effected" => $rows_affected,'having_errors' => $havingErrorClaims,'errors' => $statusRemarks, "total" => count($havingErrorClaims)]);
    }
    /**
     * get the claims from the uploaing file
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function extractClaims(Request $request) {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        //dd($request->file());
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        // Read the CSV file content into a variable
        $csvContents = file_get_contents($request->file('csv_file')->getRealPath());

        // Parse the CSV data into an array
        $csvData = str_getcsv($csvContents, "\n");

        $csvArray = [];
        foreach ($csvData as $csvRow) {
            $csvArray[] = str_getcsv($csvRow, ",");
        }
        //$this->printR($csvArray, true);
        $claims = array();
        $rows_affected = 0;
        // $this->printR($csvArray,true);
        foreach ($csvArray as $key => $item) {
            if ($key == 0)
                continue;

            if($item[2])
            array_push($claims, $item[2]);

        }
        echo implode(",",$claims);
        //$this->printR($claims,true);
    }
    /**
     * remove the claim data from the relavent tables
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
    */
    public function fixBillingReports(Request $request) {
        
        set_time_limit(0);
        
        ini_set('memory_limit', '-1');
        
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        // Read the CSV file content into a variable
        $csvContents = file_get_contents($request->file('csv_file')->getRealPath());

        // Parse the CSV data into an array
        $csvData = str_getcsv($csvContents, "\n");

        $csvArray = [];
        foreach ($csvData as $csvRow) {
            $csvArray[] = str_getcsv($csvRow, ",");
        }
        // echo count($csvArray);
        // exit;
        // $this->printR($csvArray, true);
        $claims = array();
        $rows_affected = 0;
        $arClaims = 0;
        $storedClaims =[];
        $addedClaims = [];
        $pendingClaims = [];
        // $this->printR($csvArray,true);
        $statuses = ["Billed" => 10, "On Hold" => 9]; //
        $remarkss = ["SUBMITTED TO PAYER" => 2,"Info Required"=> 24];
        foreach ($csvArray as $key => $item) {
            if ($key == 0)
                continue;

            // $this->printR($item,true);
           
          
            if(isset($item[2]) && strlen($item[2]) > 2) {
                $rows_affected++;
                $claimsNo = trim($item[2]);
                $ptId = $item[0];
                $ptName = $item[1];
                $practiceName = $item[3];
                $facilityName = $item[4];
                $payerName = $item[6];
                $billingProvider = $item[8];
                $renderingProvider = $item[7];
                $type = $item[9];
                $date = new \DateTime($item[10]);
                $dateStr = $date->format('Y-m-d');
                $dos = $dateStr;
                // echo $item[10]. PHP_EOL;
                // echo $dos;
                // exit;
                $date = new \DateTime($item[11]);
                $dateStr = $date->format('Y-m-d');
                $dob = $dateStr;
                $status = $item[12];
                $amout = str_replace("$","",$item[13]);
                $remarks = strtoupper($item[14]);
                $claim = DB::table("billing")->where("claim_no",$claimsNo)->first(["id","status_id"]);
                $payer = DB::table("payers")->where("payer_name","=",$payerName)->first(["id"]);
                $statusId = $statuses[$status];
                // $this->printR($payer, true);
                if(is_object($claim)) {
                    // array_push($addedClaims, $claimsNo);claim
                    $newRecId = $claim->id;
                    $data = $this->getBillingRowById($newRecId);
                    // $this->printR($data, true);
                    // echo    $newRecId;
                    // exit;
                    //add the billing log
                    if( DB::table("billing_logs")->where("billing_id",$newRecId)->count() == 0) {
                        echo "in iff". PHP_EOL;
                        DB::table("billing_logs")->insertGetId([
                                "user_id"           => 36256,
                                "billing_id"        => $newRecId,
                                "billing_status_id" => $statusId,
                                "details"           => "Claim entered",
                                "is_system"         => 1,
                                "created_at"        => date("Y-m-d H:i:s")
                            ]);
                    }
                    //check if the status is billed
                    if (isset($statusId) && $statusId == "10") {
                        $data = $this->getBillingRowById($newRecId);

                        $this->copyClaimToAR($data);
                    }
                }
                else {
                    $practiceId = "36325";
                    $facilityId = "36325";
                    $billingProviderId = "36327";
                    $renderingProviderId = "36327";
                   
                    $remarksId = $remarkss[$remarks];
                    if( isset($payer->id)) {
                    $claim = [
                        "patient_id"            => $ptId > 0 ? $ptId : NULL,
                        "claim_no"              => $claimsNo == 0 || is_null($claimsNo) ? NULL : $claimsNo,
                        "practice_id"           => $practiceId,
                        "facility_id"           => $facilityId > 0 ? $facilityId : $facilityId,
                        "payer_id"              => isset($payer->id) ? $payer->id : $payerName,
                        "shelter_id"            =>  NULL,
                        "dos"                   => isset($dos) && !empty($dos) ? $dos : NULL,
                        "patient_name"          => isset($ptName) && !empty($ptName) ? $ptName : NULL,
                        "bill_amount"           => $amout,
                        "remarks"               => isset($remarks) ? $remarks : NULL,
                        "remark_id"             => isset($remarksId) ? $remarksId : 0,
                        "render_provider_id"    => $billingProviderId ,
                        "billing_provider_id"   => $renderingProviderId ,
                        "visit_type_id"         =>  NULL,
                        "dob"                   =>  isset($dob) && !empty($dob) ? $dob : NULL,
                        "created_by"            => 36256,
                        "status_id"             => $statusId,
                        "created_at"            => date("Y-m-d H:i:s"),
                        'last_followup_date'    => date('Y-m-d'),
                        "dos_original"          => $item[10],
                        "dob_original"          => $item[11]
                    ];
                $newRecId = DB::table("billing")->insertGetId($claim);
                    //add the billing log
                 DB::table("billing_logs")->insertGetId([
                        "user_id"           => 36256,
                        "billing_id"        => $newRecId,
                        "billing_status_id" => $statusId,
                        "details"           => "Claim entered",
                        "is_system"         => 1,
                        "created_at"        => date("Y-m-d H:i:s")
                    ]);
                    //check if the status is billed
                    if (isset($status) && $status == "BILLED") {
                        $data = $this->getBillingRowById($newRecId);

                        $this->copyClaimToAR($data);
                    }
                    //array_push($pendingClaims, $claim);    
                }
                }
                // array_push($claims, $claimsNo);
            }
           

        }
        echo "done";
        // $this->printR($addedClaims);
        // echo PHP_EOL;
        // $this->printR($pendingClaims);
        //echo count($claims);
        // echo implode(',',$claims);
        // //echo "done:".$rows_affected;
        // //echo $arClaims;
        // // // exit;
        // // $this->printR($storedClaims);
        // echo PHP_EOL;
        // echo "done:".$rows_affected;
        // $this->printR($claims);
    }
    private function getBillingRowById($id)
    {
        $data = DB::table("billing")->where('id', $id)
            ->first();
            //->toArray();
        return is_object($data) ?  json_decode(json_encode($data), true) : [];
    }
    private function copyClaimToAR($data)
    {
        // $this->printR($data,true);
        /*!empty($data['status_id']) && !is_null($data['status_id']) ? $data['status_id'] : NULL*/
        //will use above code once AR is centerlised with status

        $newRecord = [
            'claim_no'          => $data['claim_no'],
            'practice_id'       => $data['practice_id'],
            'facility_id'       => $data['facility_id'],
            'payer_id'          => $data['payer_id'],
            'shelter_id'        => $data['shelter_id'],
            'dos'               => $data['dos'],
            'patient_name'      => isset($data['patient_name']) && !empty($data['patient_name']) ? $data['patient_name'] : NULL,
            'billed_amount'     => $data['bill_amount'],
            'status'            => $data['status_id'],
            'remarks'           => !empty($data['remark_id']) && !is_null($data['remark_id']) ? $data['remark_id'] : NULL,
            'created_by'        => !empty($data['created_by']) && !is_null($data['created_by']) ? $data['created_by'] : 0,
            'last_followup_date' => !empty($data['last_followup_date']) && !is_null($data['last_followup_date']) ? $data['last_followup_date'] : NULL,
            'next_follow_up'    => !empty($data['next_followup_date']) && !is_null($data['next_followup_date']) ? $data['next_followup_date'] : NULL,
            'dob'               => $data['dob'],
            'is_delete'         => $data['is_deleted'],
            'updated_by'        => !empty($data['updated_by']) && !is_null($data['created_by']) ? $data['updated_by'] : 0,
            'created_at'        => date("Y-m-d H:i:s"),
            'assigned_to'       => !empty($data['created_by']) && !is_null($data['created_by']) ? $data['created_by'] : 0,
            'balance_amount'    => $data['bill_amount']
        ];
        // $this->printR($newRecord,true);s
        $id = NULL;
        $claimsExist = DB::table('account_receivable')->where("claim_no", "=", $data['claim_no'])->first();
        if (!is_object($claimsExist))
            $id = DB::table('account_receivable')->insertGetId($newRecord);
       
        return $id;
    }
    /**
     * update the billing claims
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateBillingClaims(Request $request) {
        set_time_limit(0);
        
        ini_set('memory_limit', '-1');
        
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        // Read the CSV file content into a variable
        $csvContents = file_get_contents($request->file('csv_file')->getRealPath());

        // Parse the CSV data into an array
        $csvData = str_getcsv($csvContents, "\n");

        $csvArray = [];
        foreach ($csvData as $key=>$csvRow) {
            if($key > 0)
                $csvArray[] = str_getcsv($csvRow, ",");
        }
        // echo count($csvArray);
        // exit;
        $copyToAR = 0;
        $count = 0;
        if(count($csvArray)){
            foreach($csvArray as $key=>$csvRow) {
                // $this->printR($csvRow, true);
                $id = $csvRow[0];
                $claimId = $csvRow[2];
                $dos = $csvRow[10];
                $date = new \DateTime($dos);
                $dos = $date->format('Y-m-d');
                $dos_ = $date->format('m/d/Y');
                
                
                $dob = $csvRow[11];
                $date1 = new \DateTime($dob);
                $dob = $date1->format('Y-m-d');
                $dob_ = $date1->format('m/d/Y');
                
                // $date_ = new \DateTime($dos);
                // echo $claimId;
                // exit;
                // $statusId = $csvRow[10];
                if(isset($claimId) && strlen($claimId) > 2) {
                    //echo $claimId;
                    //echo PHP_EOL;
                    $updateData = ['dos' => $dos,'dos_original' => $dos_,'dob_original' => $dob_,"dob" => $dob];
                    // $this->printR($updateData, true);
                    DB::table("billing")->where("claim_no",$claimId)->update(['dos' => $dos,'dos_original' => $dos_,'dob_original' => $dob_,"dob" => $dob]);
                    $count += 1;
                }
                // if (isset($statusId) && $statusId == "10") {
                //     $copyToAR += 1;
                //     $data = $this->getBillingRowById($id);

                //     $this->copyClaimToAR($data);
                // }
            }
        }
        echo $count;
        // $this->printR($csvArray, true);
    }
     /**
     * get the claim data where dos original not null
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchClaims(Request $request) {
        
        $claims = DB::table("billing")
        
        ->whereNotNull("dos_original")
        
        ->get();
        $count = 0;
        $dateDiff = [];
        $updateCnt = 0;
        if(count($claims)) {
            foreach($claims as $claim) {
                $dob = $claim->dob;
                $date = new \DateTime($claim->dob_original);
                // echo $claim->dos_original;
                // echo  PHP_EOL;
                $id = $claim->id;
                $dobOriginal = $date->format('Y-m-d');
                if($dob != $dobOriginal) {
                    $dateTime = new \DateTime($dobOriginal);
                    $year2000 = new \DateTime('2000-01-01');
                    //if($dateTime > $year2000) 
                    {
                        $updateCnt += DB::table("billing")
        
                        ->where("id",$id)
                        
                        ->update(["dob" => $dobOriginal]);

                        $count += 1;
                        array_push($dateDiff,["dob" => $dob,"dob_original" => $claim->dob_original,"created_at" => $claim->created_at]);
                    }
                }
            }
        }
        echo $count;
        $this->printR($dateDiff, true);
    }
}
