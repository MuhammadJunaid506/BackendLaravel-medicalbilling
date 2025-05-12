<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\Credentialing;
use App\Models\PracticeLocation;
use App\Models\AccountReceivable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueDashboardController extends Controller
{
    use ApiResponseHandler, Utility;
    private $key = "";
    public function __construct()
    {
        $this->key = env("AES_KEY");
    }

    public function activePratices(Request $request){
        

        
        $sessionUserId = $this->getSessionUserId($request);
        $credentialing   = new Credentialing();
        $practices = $credentialing->fetchCredentialingUsersLI(false, "", $sessionUserId);
        $practices = $practices['practices'];
        $practicsArrFilter = [];
        foreach($practices as $practice) {
            if($practice->facility_id)
                array_push($practicsArrFilter,["facility_id" => $practice->facility_id , "doing_buisness_as" => $practice->doing_buisness_as]);
        }
        return $this->successResponse(
           $practicsArrFilter,"success");

    }

    public function collectionSummary(Request $request){


        $key = $this->key;
        $startDate = Carbon::now()->startOfMonth();
        $startDateFormat = $startDate->format('Y-m-d');
        $endDate = Carbon::now();
        $endDateFormat = $endDate->format('Y-m-d');
        $perPage = $request->has('per_page') ? $request->per_page : 10;
        if($request->has('filter_date') && $request->filter_date != null && $request->filter_date != ''  && json_decode($request->filter_date,true)  != ''){
            // dd('a',$request->filter_date,json_decode($request->filter_date,true));
            // && json_decode($request->filter_date,true)  != ''
            $filter_date = $request->has('filter_date') && $request->filter_date != null ? json_decode($request->filter_date,true) : [];
            $endDateFormat = $filter_date['endDate'];
            $startDateFormat = $filter_date['startDate'];
        }

        $sessionUser = $this->getSessionUserId($request);
        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        
        $credentiling = new Credentialing;
        $fetchPracticeAndFacility = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUser);
        $facility = $fetchPracticeAndFacility['facility'];
        $practices = $fetchPracticeAndFacility['practices'];

        $accountReceivable = AccountReceivable::select(
            "id",
            "practice_id",
            "facility_id",
            DB::raw('COUNT(cm_account_receivable.claim_no) as total_claims'),
            DB::raw('SUM(paid_amount) as collections'),
            DB::raw('(SUM(paid_amount) / COUNT(cm_account_receivable.claim_no)) as average'),
        )
        ->where('is_delete', 0)
        ->whereDate('created_at','>=',$startDateFormat)
        ->whereDate('created_at','<=',$endDateFormat);


        if($request->has('practice') && $request->practice != null && $request->practice != '' ){
            $accountReceivable = $accountReceivable
            ->with([
                'practice:user_id,doing_business_as as practice_name',
                'facility'=>function($query) use ($key){
                    $query->select('user_id', DB::raw("AES_DECRYPT(practice_name,'$key') as facility_name"));
                }
            ])
            ->where('practice_id',$request->practice)
            ->groupby('facility_id');

        }else{
            $accountReceivable = $accountReceivable
            ->with([
                'practice:user_id,practice_name as practice_name'
            ])
            ->whereIn('practice_id',$practices)
            ->whereIn('facility_id',$facility)
            ->groupby('practice_id');
        }
        $accountReceivable = $accountReceivable->paginate($perPage);
        return $this->successResponse( $accountReceivable, 'success');
    }

    public function nonClosedPayerClaims(Request $request){

        $sessionUser = $this->getSessionUserId($request);
        $credentiling = new Credentialing;
        $fetchPracticeAndFacility = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUser);
        $facility = $fetchPracticeAndFacility['facility'];
        $practices = $fetchPracticeAndFacility['practices'];

        $result = AccountReceivable::select(
            'payer_id',
            DB::raw('COUNT(claim_no) as claims'),
            DB::raw('
                CASE
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 0 AND 30 THEN "0-30"
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 31 AND 60 THEN "31-60"
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 61 AND 90 THEN "61-90"
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 91 AND 365 THEN "91-365"
                    ELSE "365+"
                END AS aging_range
            '),
        )->with('payer:id,payer_name')
        ->whereHas('revenuecyclestatus', function ($query) {
            return $query->where('considered_as_completed', 0)->whereIn('id', [10, 1, 8, 4, 7, 9, 3]);
        })
        ->wherehas('payer')
        ->where('is_delete', 0)
        ->groupBy('aging_range','payer_id')->orderby('aging_range','ASC');

        if($request->has('practice') && $request->practice != '' && $request->practice != null){
            $result = $result->where('practice_id',$request->practice);
        }else{
            $result = $result->whereIn('practice_id',$practices)->whereIn('facility_id',$facility);
        }

        $result= $result->get();
        $data=[];
        foreach ($result as $key => $value) {
            $data[$value->aging_range][]=[
                'payer_id'=>$value->payer_id,
                'payer_name'=>$value->payer?->payer_name,
                'claims'=>$value->claims,
                'range'=>$value->aging_range,
            ];

        }

        return $this->successResponse($data, 'success');
    }

    public function expectedPaymentAmountPayer(Request $request) {
     
        $sessionUser = $this->getSessionUserId($request);
        $credentiling = new Credentialing;
        $fetchPracticeAndFacility = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUser);
        $facility = $fetchPracticeAndFacility['facility'];
        $practices = $fetchPracticeAndFacility['practices'];
        $implodedPractice = implode(',',$practices);
        // $endDate = Carbon::parse(date('Y-m-d'));
        // $startDate = Carbon::parse('2024-01-01');

        $results = DB::table('account_receivable as ar')
        ->select(
            "payers.payer_name",
            "ar.payer_id",
            DB::raw('COUNT(cm_ar.id) AS claims'),
            DB::raw("SUM(CASE WHEN cm_ar.status IN (5, 2, 6) and cm_ar.practice_id IN (".$implodedPractice.") THEN 1 ELSE 0 END) AS claims_processed"),
            DB::raw("SUM(CASE WHEN cm_ar.status IN (5, 2, 6) and cm_ar.practice_id IN (".$implodedPractice.") THEN cm_ar.paid_amount ELSE 0 END) AS processed_claims_collection"),
            DB::raw("SUM(CASE WHEN cm_ar.status NOT IN (5, 2, 6) and cm_ar.practice_id IN (".$implodedPractice.") THEN 1 ELSE 0 END) AS claims_pending"),
            // DB::raw("(SELECT COUNT(id) FROM cm_account_receivable WHERE practice_id = cm_ar.practice_id AND payer_id=cm_ar.payer_id AND STATUS IN (5, 2, 6) ) AS claims_processed"),
            // DB::raw("(SELECT SUM(paid_amount) FROM cm_account_receivable WHERE practice_id = cm_ar.practice_id AND payer_id=cm_ar.payer_id AND STATUS IN (5, 2, 6) ) AS processed_claims_collection"),
            // DB::raw("(SELECT COUNT(id) FROM cm_account_receivable WHERE practice_id = cm_ar.practice_id AND payer_id=cm_ar.payer_id AND STATUS NOT IN (5, 2, 6) ) AS claims_pending"),
            DB::raw('
                CASE
                    WHEN DATEDIFF(CURDATE(), cm_ar.dos) BETWEEN 0 AND 30 THEN "0-30"
                    WHEN DATEDIFF(CURDATE(), cm_ar.dos) BETWEEN 31 AND 60 THEN "31-60"
                    WHEN DATEDIFF(CURDATE(), cm_ar.dos) BETWEEN 61 AND 90 THEN "61-90"
                    WHEN DATEDIFF(CURDATE(), cm_ar.dos) BETWEEN 91 AND 365 THEN "91-365"
                    ELSE "365+"
                END AS aging_range
            '),
        )
        ->join("payers","payers.id","=","ar.payer_id")
        // ->where('ar.dos', '>=', $startDate)
        // ->where('ar.dos', '<=', $endDate)
        // ->whereBetween('ar.dos', [$startDate, $endDate])
        // ->where('ar.practice_id', 2)
        ->where('ar.is_delete', 0)
        ->groupBy('ar.payer_id','aging_range')
        ->orderBy('payers.payer_name');

        if($request->has('practice') && $request->practice != '' && $request->practice != null){
            $results = $results->where('practice_id',$request->practice);
        }else{
            $results = $results->whereIn('practice_id',$practices)->whereIn('facility_id',$facility);
        }

        $results= $results->get();

        if($results->count() > 0) {
            foreach($results as $result) {
                $result->processed_claims_collection = round($result->processed_claims_collection,2);
                $result->expected_collection = 0;
                if($result->claims_processed > 0) {
                    $perClaimAvg = $result->processed_claims_collection / $result->claims_processed;
                    $result->expected_collection = round($perClaimAvg * $result->claims_pending,2);
                }

            }
        }

        $data=[];
        foreach ($results as $key => $value) {
            $value = json_decode(json_encode($value), true);

            $data[$value['aging_range']][]=[
                ...$value
            ];

        }
       
        return $this->successResponse($data, 'success');

    }

    public function payerAvergaeComparison(Request $request){
        // $request->validate([
        //     "dos_filter" => "required",
        //     "practice_ids" => "required"
        // ]);

        $key = env("AES_KEY");
        // $dosFilter = json_decode($request->dos_filter, true);
        // $filterStartDate = $this->formatDate($dosFilter["startDate"]);
        // $filterStartDate = date('Y-m-d',strtotime($filterStartDate));
        // $filterEndDate = $this->formatDate($dosFilter["endDate"]);
        // $filterEndDate = date('Y-m-d',strtotime($filterEndDate));


        // $filterStartDate = '2023-01-01';
        if($request->has('filter_date') && $request->filter_date != null && $request->filter_date != ''  && json_decode($request->filter_date,true)  != ''){

            $filter_date = $request->has('filter_date') && $request->filter_date != null ? json_decode($request->filter_date,true) : [];
            $filterEndDate = $filter_date['endDate'];
            $filterStartDate = $filter_date['startDate'];
        }else{

            $filterStartDate = date('Y-m-d',strtotime('-6 months'));
            $filterEndDate =  date('Y-m-d');

        }

        $monthListBetweenTwoDates = $this->monthListBetweenTwoDates(Carbon::parse($filterEndDate),Carbon::parse($filterStartDate));
        $allGeneratedMonths = $monthListBetweenTwoDates['month'];
        // dd($monthListBetweenTwoDates);

        $subQuery = "SELECT COUNT(ar.claim_no) AS total_claims,SUM(ar.paid_amount) AS total_paid,(SUM(ar.paid_amount) / COUNT(ar.claim_no)) AS average_ar,
        cm_payers.payer_name,ar.payer_id,ar.facility_id,MONTH(ar.dos) AS MONTH,YEAR(ar.dos) as year
        FROM cm_account_receivable AS ar
        INNER JOIN cm_payers ON cm_payers.id = ar.payer_id
        WHERE ar.status IN (5,6,8,2) AND ar.is_delete = 0
        AND ar.dos  >= '$filterStartDate' AND ar.dos <='$filterEndDate'
        ";
        
        if ($request->has("facility_ids")) {
            $facilityIds = json_decode($request->facility_ids, true);
            $facilityIdsStr = implode(",", $facilityIds);
            $subQuery .= " AND ar.facility_id IN ($facilityIdsStr)";
        }

        if ($request->has("practice_ids")) {
            $practiceIds = json_decode($request->practice_ids, true);
            $practiceIdsStr = implode(",", $practiceIds);
            $subQuery .= " AND ar.practice_id IN($practiceIdsStr)";
        }

        // $subQuery .= " GROUP BY MONTH(ar.dos),ar.payer_id,ar.facility_id";
        $subQuery .= " GROUP BY ar.payer_id,MONTH(ar.dos),YEAR(ar.dos)";

        $result = DB::table(DB::raw("($subQuery) AS subquery"))
        ->select([
            'total_claims',
            DB::raw('ROUND(total_paid, 2) AS total_paid'),
            DB::raw('ROUND(average_ar, 2) AS average_ar'),
            'payer_name',
            'payer_id',
            'MONTH',
            'year',
            'facility_id',
            DB::raw("(SELECT AES_DECRYPT(practice_name,'$key') FROM cm_user_ddpracticelocationinfo WHERE user_id = facility_id) AS facility_name"),
            DB::raw('0 AS percentage')
        ])->orderByDesc('total_claims')->get();

        // dd($result);
        // $this->printR($result,true);
        // Initialize the final result array
        // $finalResultArray = array();
        $months = array();
        $avgs = array();
        $eachPayerAvg = [];
        //each payer month count along with total number of avg
        if ($result->count() > 0) {
            foreach ($result as $payer) {
                if (isset($months[$payer->payer_name]))
                    $months[$payer->payer_name] += 1;
                else
                    $months[$payer->payer_name] = 1;


                if (isset($avgs[$payer->payer_name]))
                    $avgs[$payer->payer_name] +=  $payer->average_ar;
                else
                    $avgs[$payer->payer_name] = $payer->average_ar;
            }
        }


        //each payer avg
        if (count($avgs)) {
            foreach ($avgs as $key => $value) {
                $eachPayerAvg[$key] = $value / $months[$key];
            }
        }

        // dd($result,$avgs,$months,$eachPayerAvg);
        $resultant = [];
        $allPayers=[];
        if ($result->count() > 0) {
            foreach ($result as $payer) {
                $monthName = date('F', mktime(0, 0, 0, $payer->MONTH, 1));

                if ($eachPayerAvg[$payer->payer_name] > 0) {
                    $payer->percentage = round(($payer->average_ar / $eachPayerAvg[$payer->payer_name]) * 100);

                    $payer->percentage_color = "";
                    if ($payer->percentage > 100)
                        $payer->percentage_color = "green";
                    elseif ($payer->percentage < 100)
                        $payer->percentage_color = "red";
                } else
                    $payer->percentage_color = "";


                $payer->average_ar = "$ " . $payer->average_ar;
                $resultant[]=[
                    'payer_id' => $payer->payer_id,
                    'payer_name' => $payer->payer_name,
                    'percentage' => $payer->percentage,
                    'percentage_color' => $payer->percentage_color,
                    'month_name'=>$monthName,
                    'month'=>$payer->MONTH,
                    'year'=>$payer->year??'',
                ];
                $allPayers[$payer->payer_id]=[
                    'payer_id' => $payer->payer_id,
                    'payer_name' => $payer->payer_name,
                ];
                // $finalResultArray[$payer->payer_name][$monthName][$payer->facility_id] = $payer;
            }
        }

        // dd($resultant);
        $allpayerIds = array_column($resultant,'payer_id');
        $allPayers = array_values($allPayers);

        $payerWiseData=[];
        foreach ($allPayers as $key => $payers) {
      
            $searcPayerData = array_intersect($allpayerIds, [$payers['payer_id']]);
            $searchSepecificData = array_intersect_key($resultant, $searcPayerData);
            $searchSepecificData= array_values($searchSepecificData);
            // dd($searcPayerData,$searchSepecificData);
            // dd($searchSepecificData,$allGeneratedMonths);
            $monthwise=[];
            foreach ($allGeneratedMonths as $key => $mon) {
                $valuesToIntersect = [
                    ['month' => $mon['month'], 'year' => $mon['year']],
                ];
                $compareFunction = function($a, $b) {
                    if ($a['month'] == $b['month'] && $a['year'] == $b['year']) {
                        return 0; // Elements are equal
                    } elseif ($a['year'] < $b['year'] || ($a['year'] == $b['year'] && $a['month'] < $b['month'])) {
                        return -1; // $a is less than $b
                    } else {
                        return 1; // $a is greater than $b
                    }
                };

                $intersect = array_uintersect($searchSepecificData, $valuesToIntersect, $compareFunction);
                $intersect  = array_values($intersect);
                $percentage=0;
                $percentageColor='';
                if(!empty($intersect )){
                    $percentage = $intersect[0]['percentage'];
                    $percentageColor = $intersect[0]['percentage_color'];
                }

                $monthwise[]=[
                    'year'        => $mon['year'],
                    'month'       => $mon['month'],
                    'month_name'  => $mon['month_name'],
                    'percentage'=> $percentage,
                    'percentage_color'=> $percentageColor,
                ];
            }
            $payerWiseData[]=[
               ...$payers,
               'trend'=>$monthwise
            ];


        }
        $yearWise =$monthListBetweenTwoDates['yearwise'];
        return $this->successResponse(['month'=>$yearWise,'data'=>$payerWiseData], 'success');

    }

    public function monthListBetweenTwoDates($currentDate,$startDate){

        $months = [];
        $date = $startDate->copy();
        $yearWiseMonth=[];
        while ($date <= $currentDate) {
            $months[] = [
                'year' => $date->year,
                'month' => $date->month,
                'month_name' => $date->format('F'),
            ];
            $yearWiseMonth[]=$date->format('F').'-'.$date->format('y');
            $date->addMonth();
        }

        return ['month'=>$months,'yearwise'=>$yearWiseMonth];
   }

   public function denialsPracticeAndFacility(Request $request){

        $sessionUser = $this->getSessionUserId($request);
        $credentiling = new Credentialing;
        $fetchPracticeAndFacility = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUser);
        $facility = $fetchPracticeAndFacility['facility'];
        $practices = $fetchPracticeAndFacility['practices'];
        $key = env("AES_KEY");
        $accountReceivable  = AccountReceivable::select(
            'id',
            'practice_id',
            'facility_id',
            DB::raw('SUM(CASE WHEN cm_account_receivable.status = 8  THEN 1 ELSE 0 END) AS denied'),
            DB::raw('SUM(CASE WHEN cm_account_receivable.status = 3  THEN 1 ELSE 0 END) AS rejected'),
            // DB::raw('(SELECT COUNT(*) from cm_account_receivable as cr where cr.practice_id = cm_account_receivable.practice_id and cr.status = 3) as rejected '),
            // DB::raw('(SELECT COUNT(*) from cm_account_receivable as cr where cr.practice_id = cm_account_receivable.practice_id and cr.status = 8) as denied ')
        );
        
        if($request->has('practice') && $request->practice != '' && $request->practice != null){
            $accountReceivable = $accountReceivable->where('practice_id',$request->practice);
            $accountReceivable = $accountReceivable->with(['facility'=>function($query) use ($key){
                $query->select('user_id', DB::raw("AES_DECRYPT(practice_name,'$key') as facility_name"));
            }]);
            
        }else{
            $accountReceivable = $accountReceivable->whereIn('practice_id',$practices)->whereIn('facility_id',$facility);
            $accountReceivable = $accountReceivable->with('practice:user_id,practice_name');
        }


        $accountReceivable = $accountReceivable->groupby('practice_id');
        $accountReceivable = $accountReceivable->get()->toArray();
        return $this->successResponse($accountReceivable, 'success');
   }

   public function denialsPayerPercentage(Request $request)  {
        $sessionUser = $this->getSessionUserId($request);
        $credentiling = new Credentialing;
        $fetchPracticeAndFacility = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUser);
        $facility = $fetchPracticeAndFacility['facility'];
        $practices = $fetchPracticeAndFacility['practices'];
        $key = env("AES_KEY");
        $implodedPractice = implode(',',$practices);
        $endDateFormat='';
        $startDateFormat='';
        if($request->has('filter_date') && $request->filter_date != null && $request->filter_date != '' && json_decode($request->filter_date,true)  != ''){
            $filter_date = $request->has('filter_date') && $request->filter_date != null ? json_decode($request->filter_date,true) : [];
            $endDateFormat = $filter_date['endDate'];
            $startDateFormat = $filter_date['startDate'];

        }
        if($request->has('practice') && $request->practice != '' && $request->practice != null){
            if($request->has('filter_date') && $request->filter_date != null && $request->filter_date != '' && json_decode($request->filter_date,true)  != '' ){
                $accountReceivable  = AccountReceivable::select(
                'id',
                'practice_id',
                'facility_id',
                'payer_id',
                DB::raw('SUM(CASE WHEN cm_account_receivable.status in (8,3) THEN 1 ELSE 0 END) / COUNT(*) * 100 as percentage'),
                );
            }else{

                $accountReceivable  = AccountReceivable::select(
                'id',
                'practice_id',
                'facility_id',
                'payer_id',
                DB::raw('SUM(CASE WHEN cm_account_receivable.status in (8,3) THEN 1 ELSE 0 END) / COUNT(*) * 100 as percentage'),
                );
            }
            $accountReceivable = $accountReceivable->where('practice_id',$request->practice);
            
        }else{
            if($request->has('filter_date') && $request->filter_date != null && $request->filter_date != '' && json_decode($request->filter_date,true)  != ''){
                $accountReceivable  = AccountReceivable::select(
                    'id',
                    'practice_id',
                    'facility_id',
                    'payer_id',
                    DB::raw('SUM(CASE WHEN cm_account_receivable.status in (8,3) THEN 1 ELSE 0 END) / COUNT(*) * 100 as percentage'),
                );
            }else{
                $accountReceivable  = AccountReceivable::select(
                    'id',
                    'practice_id',
                    'facility_id',
                    'payer_id',
                    DB::raw('SUM(CASE WHEN cm_account_receivable.status in (8,3) THEN 1 ELSE 0 END) / COUNT(*) * 100 as percentage'),
                );
            }
           
            $accountReceivable = $accountReceivable->whereIn('practice_id',$practices)->whereIn('facility_id',$facility);
        }
        if($request->has('filter_date') && $request->filter_date != null && $request->filter_date != '' && json_decode($request->filter_date,true)  != ''){
            $accountReceivable = $accountReceivable->whereBetween('created_at',[$startDateFormat,$endDateFormat]);
        }

        $accountReceivable = $accountReceivable->with('payer:id,payer_name');;

        $accountReceivable = $accountReceivable->groupby('payer_id');
        $accountReceivable = $accountReceivable->get()->toArray();
        return $this->successResponse($accountReceivable, 'success');

   }


    public function outstandingAmount(Request $request)  {
        $sessionUser = $this->getSessionUserId($request);
        $credentiling = new Credentialing;
        $fetchPracticeAndFacility = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUser);
        $facility = $fetchPracticeAndFacility['facility'];
        $practices = $fetchPracticeAndFacility['practices'];
        $key = env('AES_KEY');
        if($request->has('filter_date') && $request->filter_date != '' && $request->filter_date != null && json_decode($request->filter_date,true)  != ''){
            $filterDate = json_decode($request->filter_date,true);
            $currentDate = Carbon::parse($filterDate['endDate']);
            $startDate = Carbon::parse($filterDate['startDate']);
        }else{
            $currentDate = Carbon::parse(date('Y-m-d'));
            $startDate = Carbon::parse(date('Y-m-01'));
        }

        $accountReceivable  = AccountReceivable::select(
            'id',
            'practice_id',
            'facility_id',
            DB::raw('sum(balance_amount) as totalamount')
        )->with('practice:user_id,practice_name');  

        if($request->has('practice') && $request->practice != '' && $request->practice != null){
            $accountReceivable=$accountReceivable->with(['practice:user_id,practice_name','facility'=>function($query) use($key){
                $query->select('user_id', DB::raw("AES_DECRYPT(practice_name,'$key') as facility_name"));
            }]);
            $accountReceivable=$accountReceivable->where('practice_id',$request->practice);  
            $accountReceivable=$accountReceivable->groupby('facility_id');  
        }else{
            $accountReceivable=$accountReceivable->with('practice:user_id,practice_name');
            $accountReceivable=$accountReceivable->whereIn('practice_id',$practices);  
            $accountReceivable=$accountReceivable->groupby('practice_id');  
        }
        $accountReceivable=$accountReceivable->get()->toArray();  

        $allAmount=array_column($accountReceivable,'totalamount');
        $sumAllAmount=array_sum($allAmount);
        return $this->successResponse([ 'total'=>$sumAllAmount, 'detail'=>$accountReceivable], 'success');

    }
    /**
     * AR trend report dashboard
     * 
     * 
     */
    public function arTrendReport(Request $request)
    {
        // $sessionUser        = $this->getSessionUserId($request);

        // $activePractices    = $this->activePractices($sessionUser);

        // $activePracticesArr = $this->stdToArray($activePractices);

        // $practiceIds        = array_column($activePracticesArr, 'facility_id');
        $sessionUser = $this->getSessionUserId($request);
        $credentiling = new Credentialing;
        $fetchPracticeAndFacility = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUser);
        $facility = $fetchPracticeAndFacility['facility'];
        $practices = $fetchPracticeAndFacility['practices'];
        $credentiling = null;
        
        if($request->has('filter_date') && $request->filter_date != '' && $request->filter_date != null && json_decode($request->filter_date,true)  != '') {
            $filterDate = json_decode($request->filter_date,true);
            if(!empty($filterDate['endDate']) && !empty($filterDate['startDate'])) {
                $currentDate = Carbon::parse($filterDate['endDate'])->format("Y-m-d");
                $startDate = Carbon::parse($filterDate['startDate'])->format("Y-m-d");
            }
            else {
                $currentDate = Carbon::now()->format("Y-m-d");
                $startDate = Carbon::now()->startOfMonth()->format("Y-m-d");    
            }
        }
        else {
            $currentDate = Carbon::now()->format("Y-m-d");
            $startDate = Carbon::now()->startOfMonth()->format("Y-m-d");
        }

        $arTrend = DB::table('ar_daily_backup as ardb')
        ->select(
            'ardb.backup_date as date',
            DB::raw('SUM(cm_ardb.claims_count) AS overall'),
            DB::raw('SUM(CASE WHEN cm_rcs.considered_as_completed = 1 THEN cm_ardb.claims_count ELSE 0 END) AS closed'),
            DB::raw('SUM(CASE WHEN cm_rcs.considered_as_completed = 0 THEN cm_ardb.claims_count ELSE 0 END) AS open')
        )
        ->join('revenue_cycle_status as rcs', 'rcs.id', '=', 'ardb.status_id')
        
        ->whereBetween('ardb.backup_date', [$startDate, $currentDate]);
        if($request->has('practice') && $request->practice != '' && $request->practice != null) {
            $facilities     = $this->getPracticeFacilities($request->practice,$sessionUser);
            $facilitiesArr  = $this->stdToArray($facilities); 
            $facilityIds    = array_column($facilitiesArr, 'facility_id');
            $arTrend        = $arTrend->where('ardb.practice_id', $request->practice)
            ->whereIn('ardb.facility_id', $facilityIds);
        }
        else {
            $arTrend = $arTrend->whereIn("ardb.practice_id",$practices)
            ->whereIn("ardb.facility_id",$facility);
        }
        $arTrend = $arTrend->groupBy('ardb.backup_date')
        ->orderBy('ardb.backup_date')
        ->get();
        
        return $this->successResponse(['ar_trend'=>$arTrend], 'success');

    }
}
