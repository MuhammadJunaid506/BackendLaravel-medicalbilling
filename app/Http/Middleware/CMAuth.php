<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use DB;
class CMAuth
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
        
        $contentTypes = $request->getAcceptableContentTypes();
        
        if( isset($contentTypes[0]) && $contentTypes[0] == "application/json" ) {
            if( $token ) {
                
                $validUser = DB::table("personal_access_tokens")->where("token",$token)
                
                ->select("users.*")
                
                ->join("users","users.id","personal_access_tokens.tokenable_id")
                
                ->first();
                
                if( is_object($validUser) )  {
                    $request->user = $validUser;
                    return $next($request)
                    ->header('X-Frame-Options', 'DENY');
                }
                else
                    return response(["message" => "Unthorized"],401)
                    ->header("X-Frame-Options","DENY");
            }
            else
                return response(["message" => "Unthorized"],401 )
                ->header("X-Frame-Options","DENY");
        }
        else 
            return response(["message" => "Bad request,invalid header present in request"],400 )
            ->header("X-Frame-Options","DENY");
    }
}
