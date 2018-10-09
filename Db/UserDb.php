<?php
/**
*+------------------
* 用户信息
*+------------------ 
*/
namespace App\Extend\Workflow\Db;

use Illuminate\Support\Facades\DB;

class UserDb{
	/**
	 * 获取用户信息
	 *
	 * @param $wf_type
	 */
	public static function GetUser() 
	{
		return  DB::connection('workflow')->table('user')->where('status','=',0)->select('id','username','role')->select();
	}
}