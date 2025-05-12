<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use DB;

class CMAuth2
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $frontEndToken = "13e2d4bb50d32aa3986f19c7518be86fe448b990d295874f4acb786dcd04f19df";

        $token = $request->bearerToken();

        
        // $requestURI = $request->server()["REQUEST_URI"];
        // echo $requestURI;
        $contentTypes = $request->getAcceptableContentTypes();
        
        if( isset($contentTypes[0]) && $contentTypes[0] == "application/json" ) {
            if( $token ) {
                
                $validUser = DB::table("personal_access_tokens")->where("token",$token)
                
                ->select("users.*")
                
                ->join("users","users.id","personal_access_tokens.tokenable_id")
                
                ->first();
                
                if( is_object($validUser) )  {
                    $request->user = $validUser;

                    $requestURI = $request->path();
                    $requestMethod = $request->method();
                    
                    $hasAccess = $this->hasAccess($validUser->id, $requestMethod, $requestURI);
                    if($hasAccess == true) {
                        return $next($request)
                        ->header('X-Frame-Options', 'DENY');
                    }
                    else {
                        return $next($request)
                        ->header('X-Frame-Options', 'DENY');
                    }
                    //return response(["message" => "Invalid credentials for the access route."], 403);

                }
                else
                    return response(["message" => "Unthorized"],401)
                    ->header("X-Frame-Options","DENY");
            }
            else
                return response(["message" => "Unthorized"],401 )
                ->header("X-Frame-Options","DENY");
        }
        else {

            return response(["message" => "Bad request,invalid header present in request"], 400 )
            ->header("X-Frame-Options","DENY");
        }
    }

    private function hasAccess($userId, $method, $requestUri) {
        if(strpos($requestUri, "api/v2/update/portals") !== false) {
            $requestUri = "api/v2/update/portals/";
        }

        $privileges = DB::table("api_uri")
            ->select("*")
            ->where("uri", 'like', substr($requestUri, 0, -5) .'%')
            ->where("method", "=", $method)
            ->get();
            if($method == 'GET') {
                return true;
            }
            if( count($privileges) == 0 ) {
                return false;
            }
            else {
                $access = DB::table("user_facility_privileges")
                ->select("*")
                ->where("user_id", "=", $userId)
                ->where("section", "=", $privileges[0]->section)
                ->where("sub_section", "=", $privileges[0]->sub_section)
                ->where($privileges[0]->access, "=", 1)
                ->get();
                if( count($access) == 0 ) {
                    return false;
                } else {
                    return true;
                }
            }

    }
}
