<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use DB;

class UserManagement extends Model
{
    use HasFactory;

    public $appTbl = "user_ddpracticelocationinfo";
    public $appTblU = "users";
    public $appKey = "";
    public function __construct() {
        $this->appKey = env("AES_KEY");
    }

    public function fetchCompanies() {
        return DB::table('companies').get();
    }


    /**
     * fetch all users with respect to company id
     * 
     * @param company_id id of the company
     */

    public function fetchAll($companyId) {

        $key = $this->appKey;

        $result = User::select(
            "users.id",
            DB::raw(" CONCAT(cm_users.last_name, ', ', cm_users.first_name) AS user_name"),
            DB::raw(" AES_DECRYPT(cm_users.email, '$key') AS email"),
            DB::raw("AES_DECRYPT(cm_users.phone, '$key') AS phone"),
            "users.gender",
            "users.profile_image",
            DB::raw("CONCAT(cm_users.city, ' ', cm_users.state) AS location"),
            "users.deleted",
            "users.is_complete",
            "users.status",
            "roles.role_name",
            DB::raw("(SELECT session_buid_at
                   FROM cm_user_accountactivity
                   WHERE user_id = cm_users.id
                   ORDER BY session_buid_at DESC
                   LIMIT 1) AS lastLogin")
        )
            ->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")
            ->leftJoin("companies", "companies.id", "=", "user_company_map.company_id")
            ->leftJoin("user_role_map", "user_role_map.user_id", "=", "users.id")
            ->leftJoin("roles", function ($join) {
                $join->on("roles.id", "=", "user_role_map.role_id")
                    ->whereNotIn("roles.id", [3, 9]);
            })
            ->where("companies.id", $companyId)
            ->groupBy("users.id")
            ->orderBy("lastLogin", "DESC")
            ->paginate($this->cmperPage);

            return $result;
    }

    /**
     * fetch the Practicies Of ECA
     *
     * @param status status of the user
     */

    public function getPractices($status)
     {
         $tbl = $this->appTbl;
         $tblU = $this->appTblU;
         $appKey = $this->appKey;
 
         return DB::table($tblU.' as u')
             ->select("u.id as value", DB::raw("AES_DECRYPT(cm_pli.doing_buisness_as,'$appKey') as label"))
             ->join('user_role_map as urm', function ($join) use ($status) {
                 $join->on('urm.user_id', '=', 'u.id')
                     ->where('urm.role_id', '=', 9)
                     ->where("u.deleted","=", $status);
             })
             ->join($tbl.' as pli', function ($join) {
                 $join->on([
                     ['pli.user_id', '=', 'u.id'],
                     ['pli.user_parent_id', '=', 'u.id']
                 ]);
             })
             ->orderBy('label', 'asc')
 
             ->get();

        // return 'practicies';
     }

    
    
     /**
     * fetch the Facility Of ECA
     *
     * @param parentId parent of the facility
     * @param status get by status of the facility
     */
    function getFacilities($parentId, $status)
    {
        $tbl = $this->appTbl;
        $tblU = $this->appTblU;
        $appKey = $this->appKey;

        $locations = DB::table($tbl . " AS pli")

            ->select([DB::raw("AES_DECRYPT(cm_pli.practice_name,'$appKey') as label"), "pli.user_id as value"])
            ->join($tblU." AS u",function($join) use ($status) {
                $join->on('pli.user_id', '=', 'u.id')
                     ->where("u.deleted","=", $status);
            });
            
        if (is_array($parentId))
            $locations = $locations->whereIn("pli.user_parent_id", $parentId);
        else
            $locations = $locations->where("pli.user_parent_id", "=", $parentId);



        return $locations->orderByRaw("cm_pli.user_parent_id ASC, cm_pli.user_id ASC")

            ->get();
    }



    /**
     * add new user...
     * 
     * @param user data of the user
     */

     function addUser($user, $companyId, $roleId) {
        $key = $this->appKey;
        $emailStr = $user['email'];
        $user['email'] = DB::raw("AES_ENCRYPT('" . $emailStr . "', '$key')");
        $user['password'] = Hash::make($user['password']);

        $userId = DB::table('users')->insertGetId($user);

        DB::table('user_company_map')->insertGetId(["company_id" =>$companyId,"user_id" => $userId]);
        DB::table('user_role_map')->insertGetId(["role_id" => $roleId, "user_id" => $userId]);

        return $userId;

     }


    /**
     * add new user...
     * 
     * @param user data of the user
     */

     function getUserById($userId) {
        $key = $this->appKey;

        $user = User::select(
            'first_name', 
            'last_name',
            DB::raw(" AES_DECRYPT(cm_users.email, '$key') AS email"),
            DB::raw("AES_DECRYPT(cm_users.phone, '$key') AS phone"),
            DB::raw("AES_DECRYPT(cm_users.address_line_one, '$key') AS address_line_one"),
            DB::raw("AES_DECRYPT(cm_users.address_line_two, '$key') AS address_line_two"),
            'role_id', 
            'gender',
            'zip_code', 
            'city', 
            'state',
            'deleted'
            )
            ->leftJoin('user_role_map', 'user_role_map.user_id', '=', 'users.id')
            ->leftJoin('roles', 'roles.id', '=', 'user_role_map.role_id')
            ->where('users.id', $userId)
            ->first();

        return $user;

     }

    /**
     * update existing user...
     * 
     * @param user data of the user
     */

     function updateUser($userId, $userData, $role) {
        $key = $this->appKey;
        
        $phoneStr = $userData['phone'];
        $address1Str = $userData['address_line_one'];
        $address2Str = $userData['address_line_two'];
        if(!empty($userData['email'])){
            $emailStr = $userData['email'];
            $userData['email'] = DB::raw("AES_ENCRYPT('" . $emailStr . "', '$key')");
        }
        $userData['phone'] = DB::raw("AES_ENCRYPT('" . $phoneStr . "', '$key')");
        $userData['address_line_one'] = DB::raw("AES_ENCRYPT('" . $address1Str . "', '$key')");
        $userData['address_line_two'] = DB::raw("AES_ENCRYPT('" . $address2Str . "', '$key')");
       

        $affectedRows1 = DB::table('users')->where('id', $userId)->update($userData);
        // if(!$affectedRows) {
        //     return ["first_query_affected_rows" => $affectedRows];
        // }
        
        $affectedRows2 = DB::table('user_role_map')->where('user_id', $userId)->update(["role_id" => $role]);

        return ["first_query_affected_rows" => $affectedRows1, "second_query_affected_rows" => $affectedRows2];

     }


    public function fetchAllPracticies($userId, $flag) {
        $key = $this->appKey;

        $practices = DB::table('user_ddpracticelocationinfo')
        ->join('users', 'users.id', '=', 'user_ddpracticelocationinfo.user_id')
        ->selectRaw("cm_user_ddpracticelocationinfo.user_id as user_id, AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') as practice_name, cm_users.deleted")
        ->orderBy('practice_name', 'asc')
        ->get()->toArray();

        // return $practices;
        $specificFacilities = [];
        if($flag == 'system') {
            $myFacilities = DB::table('emp_location_map')
            ->join('user_ddpracticelocationinfo as PLI', 'PLI.user_id', '=', 'emp_location_map.location_user_id')
            ->where('emp_location_map.emp_id', $userId)
            ->selectRaw("cm_PLI.user_id as value, AES_DECRYPT(cm_PLI.practice_name, '$key') as label")
            ->orderBy('label', 'asc')
            ->get();
            
            if(count($myFacilities)) {
                foreach($myFacilities as $facility) {
                    $specificFacilities[$facility->value] = $facility;
                }
            }
            // return $facilities;
        }

        if($flag == 'provider') {
            $myFacilities = DB::table('individualprovider_location_map')
            ->join('user_ddpracticelocationinfo as PLI', 'PLI.user_id', '=', 'individualprovider_location_map.location_user_id')
            ->where('individualprovider_location_map.user_id', $userId)
            ->selectRaw("cm_PLI.user_id as value, AES_DECRYPT(cm_PLI.practice_name, '$key') as label")
            ->orderBy('label', 'asc')
            ->get();
            
            if(count($myFacilities)) {
                foreach($myFacilities as $facility) {
                    $specificFacilities[$facility->value] = $facility;
                }
            }
        }

        
        return ["sys_facilties" => $practices,"user_facilties" => $specificFacilities];

    }

    public function addUserToFacility($userId, $facilities, $flag) {
        $key = $this->appkey;

        $facilities = explode(',', $facilities);

        $errorSummary = [];
        if($flag == 'system') {

            foreach ($facilities as $facility) {
                $exists = DB::table('emp_location_map')
                    ->where('emp_id', $userId)
                    ->where('location_user_id', $facility)
                    ->exists();
            
                if ($exists) {
                   array_push($errorSummary,["is_success" => false,"message" => "User already assigned aginst this facility","data" => ["user_id" => $userId, "facilityId" => $facility]]);
                } else {
                    $result = DB::table('emp_location_map')->insertGetId([
                        'emp_id' => $userId,
                        'location_user_id' => $facility
                    ]);
                    array_push($errorSummary,["is_success" => true,"message" => "User assigned to facility successfully.","data" => ["user_id" => $userId, "facilityId" => $facility]]);
                }
            }
    
            return $errorSummary;
        }

        if($flag == 'provider') {
            foreach ($facilities as $facility) {
                $exists = DB::table('individualprovider_location_map')
                    ->where('emp_id', $userId)
                    ->where('location_user_id', $facility)
                    ->exists();
            
                if ($exists) {
                   array_push($errorSummary,["is_success" => false,"message" => "User already assigned aginst this facility","data" => ["user_id" => $userId, "facilityId" => $facility]]);
                } else {
                    $result = DB::table('individualprovider_location_map')->insertGetId([
                        'emp_id' => $userId,
                        'location_user_id' => $facility
                    ]);
                    array_push($errorSummary,["is_success" => true,"message" => "User assigned to facility successfully.","data" => ["user_id" => $userId, "facilityId" => $facility]]);
                }
            }
    
            
            return $errorSummary;
        }

        return 'invalid usertype provided';
    }

    public function deleteUserFromFacility($userId, $facilityId, $flag) {
        $key = $this->appKey;

        if($flag == 'system') {
            $result =  DB::table('emp_location_map')
            ->where('emp_id', '=', $userId)
            ->where('location_user_id', '=', $facilityId)
            ->delete();
            if($result) {
                $result= DB::table('user_facility_privileges')
                        ->where('user_id', $userId)
                        ->where('facility_id', $facilityId)
                        ->delete();
            }

            return $result;
        }

        if($flag == 'provider') {
            $result =  DB::table('individualprovider_location_map')
            ->where('user_id', '=', $userId)
            ->where('location_user_id', '=', $facilityId)
            ->delete();

            return $result;
        }

        return ['is_success' => false, 'message' => 'invalid user type provided.'];
    }

    public function changePassword($userId, $newPassword) {
        $newPassword = Hash::make($newPassword);
        $affectedRows = DB::table('users')->where('id', $userId)->update(['password' => $newPassword]);

        return $affectedRows;
    }



    // methods for roles and previliges section starts from here...

    public function getProvidersByFacilityId($facilityId){

        $result = DB::table('user_dd_individualproviderinfo')
        ->selectRaw('user_id as value, concat(first_name," ", last_name) as label')
        ->join('users', 'users.id', '=', 'user_dd_individualproviderinfo.user_id')
        ->where('parent_user_id', $facilityId)
        ->get();

        return $result;
    }

    public function updatePriviligesByUser($userId, $data) {

        DB::table('user_facility_privileges')
        ->where('user_id', $userId)
        ->whereIn('facility_id', array_column($data, 'facility_id'))
        ->delete();

        $resultsArray = [];

        foreach ($data as $userData) {
            $result = DB::table('user_facility_privileges')->insertGetId($userData);
            $resultsArray[] = $result;
        }

        return $resultsArray;
    }

    public function deleteBulkFacilities($userId, $facilityIds, $flag) {

        $resultsArray = [];

        if($flag == 'system') {

            foreach ($facilityIds as $facilityId) {

                $result = DB::table('emp_location_map')
                ->where('emp_id', $userId)
                ->where('location_user_id', $facilityId)
                ->delete();

                if($result) {
                    $result= DB::table('user_facility_privileges')
                            ->where('user_id', $userId)
                            ->where('facility_id', $facilityId)
                            ->delete();
                }

                $resultsArray[] = $result;
            }

            return $resultsArray;

        }

        if($flag == 'provider') {

            foreach ($facilityIds as $facilityId) {

                $result = DB::table('individualprovider_location_map')
                ->where('user_id', $userId)
                ->where('location_user_id', $facilityId)
                ->delete();

                if($result) {
                    $result= DB::table('user_facility_privileges')
                            ->where('user_id', $userId )
                            ->where('facility_id', $facilityId)
                            ->delete();
                }

                $resultsArray[] = $result;
            }
            return $resultsArray;
        }

        return 'invalid user-type.';
        
    }

    public function getAllPrivileges() {
        
        $result = DB::table('privileges')
        ->select('*')
        ->get();

        return $result;
    }

    public function addRolePrevileges($data) {

        $resultsArray = [];

        foreach ($data as $userData) {
            $result = DB::table('role_privileges')->insertGetId($userData);
            array_push($resultsArray, $result);
        }

        return $resultsArray;
    }

    public function updateRolePrevileges($roleId, $data) {
        DB::table('role_privileges')
        ->where('role_id', $roleId)
        ->delete();

        // dd($data);

        $resultsArray = [];

        foreach ($data as $userData) {
            $userData['role_id'] = $roleId;
            $result = DB::table('role_privileges')->insertGetId($userData);
            array_push($resultsArray, $result);
        }

        return $resultsArray;
    }

    public function getUserFaclityPrivileges($userId, $facilityId) {
        $result = DB::table('user_facility_privileges')
                ->select('route_id', 'user_id', 'privilege_id', 'facility_id', 'section', 'sub_section', 'view', 'create', 'edit', 'delete', 'admin')
                ->where('user_id', $userId)
                ->where('facility_id', $facilityId)
                ->get();

        return $result;
    }

    public function resetUserFacilityPrivileges($userId, $facilityId) {
        $result = DB::table('user_facility_privileges')
                ->where('user_id', $userId)
                ->where('facility_id', $facilityId)
                ->delete();

        return $result;
    }

    public function getUserGenericPrivileges($userId) {
       
        $result = DB::table('user_facility_privileges')
                ->select('route_id', 'user_id', 'privilege_id', 'facility_id', 'section', 'sub_section', 'view', 'create', 'edit', 'delete', 'admin')
                ->where('user_id', $userId)
                ->where('facility_id', 0)
                ->where('privilege_id', 0)
                ->get();

        if(count($result) == 0) {
            $result = DB::table('role_privileges')
            ->select('route_id', 'section', 'sub_section', 'view', 'create', 'edit', 'delete', 'admin')
            ->leftJoin('user_role_map', 'role_privileges.role_id', '=', 'user_role_map.role_id')
            ->where('user_role_map.user_id', $userId)
            ->get();
        }
                
        return $result;
    }

    public function getRolePrivileges($roleId) {
        $result = DB::table('role_privileges')
        ->select('route_id', 'role_id', 'section', 'sub_section', 'view', 'create', 'edit', 'delete', 'admin')
        ->where('role_id', $roleId)
        ->get();

        if(count($result) == 0) {
            $result = DB::table('privileges')
                ->select('*')
                ->get();
        }

        return $result;
    }

    public function getAtomicPrivilege($data) {

        $result = DB::table('user_facility_privileges')
                ->select('route_id', 'user_id', 'privilege_id', 'facility_id', 'section', 'sub_section', 'view', 'create', 'edit', 'delete', 'admin')
                ->where('user_id', $data['userId'])
                // ->where('privilege_id', $data['roleId'])
                ->where('facility_id', $data['facilityId'])
                ->where('section', $data['section'])
                ->where('sub_section', $data['subSection'])
                ->get();

        return $result;
    }

    public function getLoginDataByUserId( $userId) {
        $result = DB::table('user_accountactivity')
                    ->select('session_buid_at as login', 'session_expired_at as logout', 'os', 'browser', 'ip as ipAddress', 'city as location', 'lon as longitude', 'lat as latitude', 'timezone')
                    ->where('user_id', $userId)
                    ->get();

        return $result;
    }

    public function getSystemActivityByUserId($userId) {
        $result = DB::table('system_logs')
        // ->selectRaw('')
        ->select(DB::raw("ifnull(cm_system_logs.created_at, cm_system_logs.updated_at) as dateTime"),'action', 'action_data as log', 'effected_component as section')
        ->selectRaw('concat(first_name, " ", last_name) as affected')
        ->leftJoin('users', 'users.id', '=', 'system_logs.user_id')
        ->where('logged_by', $userId)
        ->get();

        return $result;
    }

    public function getProviderPortalEditByUserId($userId) {
        
        $result = DB::table('individualprovider_location_map')
                ->whereIn('location_user_id', function ($query) use($userId) {
                    $query->select('facility_id')
                        ->from('user_facility_privileges')
                        ->where('user_id', $userId)
                        ->where('section', 'Portals')
                        ->where('edit', 1);
                })
                ->select('user_id');
        return $result->get();
    }

    public function getDirectoryAdminByUserId($userId) {

        $userIds = DB::table('individualprovider_location_map')
            ->whereIn('location_user_id', function ($query) use($userId) {
                $query->select('facility_id')
                    ->from('user_facility_privileges')
                    ->where('user_id', $userId)
                    ->where('section', 'Directory')
                    ->where('sub_section', 'Profile')
                    ->where('admin', 1);
            })
            ->select('user_id');

        $userIds = $userIds->union(function ($query) use($userId) {
            $query->select('user_parent_id as user_id')
                ->from('user_ddpracticelocationinfo')
                ->whereIn('user_id', function ($subQuery) use($userId) {
                    $subQuery->select('facility_id')
                        ->from('user_facility_privileges')
                        ->where('user_id', $userId)
                        ->where('section', 'Directory')
                        ->where('sub_section', 'Profile')
                        ->where('admin', 1);
                });
            });

        $userIds = $userIds->union(function ($query) use($userId) {
            $query->select('facility_id as user_id')
                ->from('user_facility_privileges')
                ->where('user_id', $userId)
                ->where('section', 'Directory')
                ->where('sub_section', 'Profile')
                ->where('admin', 1);
            });

        return $userIds->get();
    }

    public function getDirectoryAdminDeniedByUserId($userId) {

        $userIds = DB::table('individualprovider_location_map')
            ->whereIn('location_user_id', function ($query) use($userId) {
                $query->select('facility_id')
                    ->from('user_facility_privileges')
                    ->where('user_id', $userId)
                    ->where('section', 'Directory')
                    ->where('sub_section', 'Profile')
                    ->where('admin', 0);
            })
            ->select('user_id');

        $userIds = $userIds->union(function ($query) use($userId) {
            $query->select('user_parent_id as user_id')
                ->from('user_ddpracticelocationinfo')
                ->whereIn('user_id', function ($subQuery) use($userId) {
                    $subQuery->select('facility_id')
                        ->from('user_facility_privileges')
                        ->where('user_id', $userId)
                        ->where('section', 'Directory')
                        ->where('sub_section', 'Profile')
                        ->where('admin', 0);
                });
            });

        $userIds = $userIds->union(function ($query) use($userId) {
            $query->select('facility_id as user_id')
                ->from('user_facility_privileges')
                ->where('user_id', $userId)
                ->where('section', 'Directory')
                ->where('sub_section', 'Profile')
                ->where('admin', 0);
            });

        return $userIds->get();
    }

    public function getDirectoryAccessByUserId($userId) {

        $query = DB::table('individualprovider_location_map')
          ->select('user_id')
          ->whereIn('location_user_id', function($q) use ($userId) {
            $q->select('facility_id')
              ->from('user_facility_privileges')
              ->where('user_id', $userId)
              ->where('section', 'Directory')
              ->where('sub_section', 'Profile')
              ->where('edit', 1);
          })
          ->groupBy('user_id');
      
        // First union
        $query->union(function($q) use ($userId) {
          $q->select('user_parent_id as user_id')  
            ->from('user_ddpracticelocationinfo')
            ->whereIn('user_id', function($q1) use ($userId) {
              $q1->select('facility_id')
                ->from('user_facility_privileges')
                ->where('user_id', $userId)
                ->where('section', 'Directory')
                ->where('sub_section', 'Profile')
                ->where('edit', 1); 
            });
        });
      
        // Second union
        $query->union(function($q) use ($userId) {
          $q->select('facility_id as user_id')
            ->from('user_facility_privileges')
            ->where('user_id', $userId)
            ->where('section', 'Directory')
            ->where('sub_section', 'Profile')
            ->where('edit', 1)
            ->groupBy('facility_id');
        });
      
        return $query->get();
      
      }


    public function getDirectoryAccessDeniedByUserId($userId) {

        $query = DB::table('individualprovider_location_map')
          ->select('user_id')
          ->whereIn('location_user_id', function($q) use ($userId) {
            $q->select('facility_id')
              ->from('user_facility_privileges')
              ->where('user_id', $userId)
              ->where('section', 'Directory')
              ->where('sub_section', 'Profile')
              ->where('edit', 0);
          })
          ->groupBy('user_id');
      
        // First union
        $query->union(function($q) use ($userId) {
          $q->select('user_parent_id as user_id')  
            ->from('user_ddpracticelocationinfo')
            ->whereIn('user_id', function($q1) use ($userId) {
              $q1->select('facility_id')
                ->from('user_facility_privileges')
                ->where('user_id', $userId)
                ->where('section', 'Directory')
                ->where('sub_section', 'Profile')
                ->where('edit', 0); 
            });
        });
      
        // Second union
        $query->union(function($q) use ($userId) {
          $q->select('facility_id as user_id')
            ->from('user_facility_privileges')
            ->where('user_id', $userId)
            ->where('section', 'Directory')
            ->where('sub_section', 'Profile')
            ->where('edit', 0)
            ->groupBy('facility_id');
        });
      
        return $query->get();
      
      }


    public function getProfileViewByUserId($userId) {

        $query = DB::table('individualprovider_location_map')
          ->select('user_id')
          ->whereIn('location_user_id', function($q) use ($userId) {
            $q->select('facility_id')
              ->from('user_facility_privileges')
              ->where('user_id', $userId)
              ->where('section', 'Directory')
              ->where('sub_section', 'Profile')
              ->where('view', 1);
          })
          ->groupBy('user_id');
      
        // First union
        $query->union(function($q) use ($userId) {
          $q->select('user_parent_id as user_id')  
            ->from('user_ddpracticelocationinfo')
            ->whereIn('user_id', function($q1) use ($userId) {
              $q1->select('facility_id')
                ->from('user_facility_privileges')
                ->where('user_id', $userId)
                ->where('section', 'Directory')
                ->where('sub_section', 'Profile')
                ->where('view', 1); 
            });
        });
      
        // Second union
        $query->union(function($q) use ($userId) {
          $q->select('facility_id as user_id')
            ->from('user_facility_privileges')
            ->where('user_id', $userId)
            ->where('section', 'Directory')
            ->where('sub_section', 'Profile')
            ->where('view', 1)
            ->groupBy('facility_id');
        });
      
        return $query->get();
      
    }


    public function getProfileViewDeniedByUserId($userId) {

        $query = DB::table('individualprovider_location_map')
          ->select('user_id')
          ->whereIn('location_user_id', function($q) use ($userId) {
            $q->select('facility_id')
              ->from('user_facility_privileges')
              ->where('user_id', $userId)
              ->where('section', 'Directory')
              ->where('sub_section', 'Profile')
              ->where('view', 0);
          })
          ->groupBy('user_id');
      
        // First union
        $query->union(function($q) use ($userId) {
          $q->select('user_parent_id as user_id')  
            ->from('user_ddpracticelocationinfo')
            ->whereIn('user_id', function($q1) use ($userId) {
              $q1->select('facility_id')
                ->from('user_facility_privileges')
                ->where('user_id', $userId)
                ->where('section', 'Directory')
                ->where('sub_section', 'Profile')
                ->where('view', 0); 
            });
        });
      
        // Second union
        $query->union(function($q) use ($userId) {
          $q->select('facility_id as user_id')
            ->from('user_facility_privileges')
            ->where('user_id', $userId)
            ->where('section', 'Directory')
            ->where('sub_section', 'Profile')
            ->where('view', 0)
            ->groupBy('facility_id');
        });
      
        return $query->get();
      
    }

    public function resetUserGenericPrivileges($userId) {

        $result = DB::table('user_facility_privileges')
        ->where('user_id', $userId)
        ->where('facility_id', 0)
        ->where('privilege_id', 0)
        ->delete();

        return $result;
    }

    public function getAllMainNavigations() {
    
    $result = DB::table('routes')
    ->select('id', 'name', 'sort_by')
    ->where('is_navigation', 1)
    ->where('parent_navigation_id', 0)
    ->whereNotIn('id', [15, 24, 58, 84, 97])
    ->orderBy('sort_by', 'asc')
    ->get();

    return $result;
    }

    public function getNavigationByUserId($userId) {

        $navigation = DB::table('routes')
        ->select('routes.id', 'name', 'sort_by')
            ->whereIn('id', function ($query) use ($userId) {
                $query->select('parent_navigation_id')
                    ->from('routes')
                    ->whereIn('id', function ($query) use ($userId) {
                        $query->select('route_id')
                            ->from('user_facility_privileges')
                            ->where('user_id', $userId)
                            ->where('view', 1)
                            ->groupBy('route_id');
                    });
            })
            ->union(function ($query) use ($userId) {
                $query->select('routes.id', 'name', 'sort_by')
                    ->from('routes')
                    ->whereIn('id', function ($query) use ($userId) {
                        $query->select('route_id')
                            ->from('user_facility_privileges')
                            ->where('user_id', $userId)
                            ->where('view', 1);
                    })
                    ->where('parent_navigation_id', 0)
                    ->where('is_navigation', 1);
            })
            ->union(function ($query) use ($userId) {
                $query->select('routes.id', 'name', 'sort_by')
                    ->from('routes')
                    ->whereIn('id', function ($query) use ($userId) {
                        $query->select('parent_navigation_id')
                            ->from('routes')
                            ->whereIn('id', function ($query) use ($userId) {
                                $query->select('route_id')
                                    ->from('role_privileges')
                                    ->whereNotIn('route_id', function ($query) use ($userId) {
                                        $query->select('route_id')
                                            ->from('user_facility_privileges')
                                            ->where('user_id', $userId);
                                    })
                                    ->where('role_id', function ($query) use ($userId) {
                                        $query->select('role_id')
                                            ->from('user_role_map')
                                            ->where('user_id', $userId);
                                    })
                                    ->where('view', 1);
                            })
                            ->where('parent_navigation_id', '<>', 0);
                    });
            })
            ->union(function ($query) use ($userId) {
                $query->select('routes.id', 'name', 'sort_by')
                    ->from('routes')
                    ->whereIn('id', function ($query) use ($userId) {
                        $query->select('route_id')
                            ->from('role_privileges')
                            ->whereNotIn('route_id', function ($query) use ($userId) {
                                $query->select('route_id')
                                    ->from('user_facility_privileges')
                                    ->where('user_id', $userId);
                            })
                            ->where('role_id', function ($query) use ($userId) {
                                $query->select('role_id')
                                    ->from('user_role_map')
                                    ->where('user_id', $userId);
                            })
                            ->where('view', 1);
                    })
                    ->where('parent_navigation_id', 0)
                    ->where('is_navigation', 1);
            })
            ->orderBy('sort_by', 'asc')
            ->get();


        return $navigation;

    }

    public function updateUserNavigation($userId, $parentNavigationIds) {   

        $result =  DB::table('user_facility_privileges')
            ->whereIn('id', function ($query) use($userId, $parentNavigationIds) {
                $query->select('id')
                    ->from('user_facility_privileges')
                    ->where(function ($subquery) use($parentNavigationIds) {
                        $subquery->whereIn('route_id', function ($nestedQuery) use($parentNavigationIds) {
                                $nestedQuery->select('id')
                                    ->from('routes')
                                    ->whereIn('parent_navigation_id', $parentNavigationIds);
                            })
                            ->orWhereIn('route_id', $parentNavigationIds);
                    })
                    ->where('user_id', $userId);
            })
            ->update(['admin' => 0, 'view' => 0, 'create' => 0, 'edit' => 0, 'delete' => 0]);
            return $result;
    }

    public function getRoleWiseUsers($roleId) {
        
        $users = DB::table('users')
            ->select("users.id", "first_name", "last_name")
            ->leftJoin('user_role_map', 'user_role_map.user_id', '=', 'users.id')
            ->where('user_role_map.role_id', $roleId)
            ->get();
        return $users;
    }
      

}
