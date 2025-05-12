<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use DB;

class UserManagementMiddleware
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
        // $request->validate([
        //     'userId' => 'required'
        // ]);
        // dd($request->all());
        $userId =  $request->has("userId") ?  $request->get('userId') : 0;

        $flag = '';
        $userExists = DB::table('users')
            ->leftJoin('user_role_map as urm', 'urm.user_id', '=', 'users.id')
            ->where('users.id', '=', $userId)
            ->where('urm.role_id', '=', 4)
            ->exists();

        if ($userExists) {
            // User exists
            $flag = 'provider';
        } else {
            // User does not exist
            $flag = 'system';
        }

        //exit($flag);
        $request->user_type = $flag; 
        return $next($request);
    }
}
