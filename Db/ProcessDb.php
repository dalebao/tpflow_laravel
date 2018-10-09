<?php
/**
*+------------------
* 工作流步骤
*+------------------ 
*/

namespace App\Extend\Workflow\Db;

use Illuminate\Support\Facades\DB;

class ProcessDb{
	/**
	 * 根据ID获取流程信息
	 *
	 * @param $pid 步骤编号
	 */
	public static function GetProcessInfo($pid)
	{
		$info = (array)DB::connection('workflow')->table('flow_process')
				->select('id','process_name','process_type','process_to','auto_person','auto_sponsor_ids','auto_role_ids',
                    'auto_sponsor_text','auto_role_text','range_user_ids','range_user_text','is_sing','sign_look','is_back')
				->find($pid);
		if($info['auto_person']==3){ //办理人员
			$ids = explode(",",$info['range_user_text']);
			$info['todo'] = ['ids'=>explode(",",$info['range_user_ids']),'text'=>explode(",",$info['range_user_text'])];
		}
		if($info['auto_person']==4){ //办理人员
			$info['todo'] = $info['auto_sponsor_text'];
		}
		if($info['auto_person']==5){ //办理角色
			$info['todo'] = $info['auto_role_text'];
		}
		return $info;
	}
	/**
	 * 获取下个审批流信息
	 *
	 * @param $wf_type 单据表
	 * @param $wf_fid  单据id
	 * @param $pid   流程id
	 **/
	public static function GetNexProcessInfo($wf_type,$wf_fid,$pid)
	{
//		$info = Db::name($wf_type)->find($wf_fid);
		$nex = (array)DB::connection('workflow')->table('flow_process')->find($pid);
		if($nex['process_to'] !=''){
		$nex_pid = explode(",",$nex['process_to']);
		$out_condition = json_decode($nex['out_condition'],true);
			if(count($nex_pid)>=2){
			//多个审批流
				foreach($out_condition as $key=>$val){
					$where =implode(",",$val['condition']);
					//根据条件寻找匹配符合的工作流id
					$info = Db::name($wf_type)->where($where)->where('id','=',$wf_fid)->first();
					if($info){
						$nexprocessid = $key; //获得下一个流程的id
						break;	
					}
				}
				$process = self::GetProcessInfo($nexprocessid);
			}else{
				$process = self::GetProcessInfo($nex_pid);	
			}
		}else{
			$process = ['auto_person'=>'','id'=>'','process_name'=>'END','todo'=>'结束'];
		}
		return $process;
	}
	/**
	 * 获取前步骤的流程信息
	 *
	 * @param $runid
	 */
	public static function GetPreProcessInfo($runid)
	{
		$pre = [];
		$pre_n = (array)DB::connection('workflow')->table('run_process')->find($runid);
		//获取本流程中小于本次ID的步骤信息
		$pre_p = DB::connection('workflow')->table('run_process')
			 ->where('run_flow','=',$pre_n['run_flow'])
			 ->where('run_id','=',$pre_n['run_id'])
			 ->where('id','<',$pre_n['id'])
			 ->select('run_flow_process')->get()->toArray();
		//遍历获取小于本次ID中的相关步骤
		foreach($pre_p as $k=>&$v){
            $v = (array)$v;
			$pre[] = DB::connection('workflow')->table('flow_process')->where('id','=',$v['run_flow_process'])->first();
		}
		unset($v);
		$prearray = [];
		if(count($pre)>=1){
			$prearray[0] = '退回制单人修改';
			foreach($pre as $k => $v){
				if($v['auto_person']==4){ //办理人员
					$todo = $v['auto_sponsor_text'];
				}
				if($v['auto_person']==5){ //办理角色
					$todo = $v['auto_role_text'];
				}
				$prearray[$v['id']] = $v['process_name'].'('.$todo.')';
			}
			}else{
			$prearray[0] = '退回制单人修改';	
		}
		return $prearray;
	}
	/**
	 * 获取前步骤的流程信息
	 *
	 * @param $runid
	 */
	public static function Getrunprocess($pid,$run_id)
	{
		$pre_n = DB::connection('workflow')->table('run_process')->where('run_id','=',$run_id)->where('run_flow_process','=',$pid)->first();
		return $pre_n;
	}
	
	/**
	 * 获取第一个流程
	 *
	 * @param $wf_id
	 */
	public static function getWorkflowProcess($wf_id) 
	{
		$flow_process = DB::connection('workflow')
            ->table('flow_process')
            ->where('is_del','=',0)
            ->where('flow_id','=',$wf_id)
            ->select()
            ->get()
            ->toArray();
		//找到 流程第一步
        $flow_process_first = array();
        foreach($flow_process as &$value)
        {
            $value = (array)$value;
            if($value['process_type'] == 'is_one')
            {
                $flow_process_first = $value;
                break;
            }
        }
        unset($value);
		if(!$flow_process_first)
        {
            return  false;
        }
		return $flow_process_first;
	}
	/**
	 * 流程日志
	 *
	 * @param $wf_fid
	 * @param $wf_type
	 */
	public static function RunLog($wf_fid,$wf_type) 
	{
		$run_log = DB::connection('workflow')->table('run_log')->where('from_id','=',$wf_fid)->where('from_table','=',$wf_type)->get()->toArray();
		foreach($run_log as $k=>&$v)
        {
            $v = (array)$v;
            $run_log[$k]['user'] =DB::connection('workflow')->table('user')->where('id','=',$v['uid'])->value('username');
        }
        unset($v);
		return $run_log;
	}
	
}