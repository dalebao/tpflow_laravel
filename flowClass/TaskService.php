<?php
/**
*+------------------
* 工作流任务服务
*+------------------ 
*/

namespace App\Extend\Workflow\flowClass;

use App\Extend\Workflow\flowClass\Command\BackFlow;
use App\Extend\Workflow\flowClass\Command\SingFlow;
use App\Extend\Workflow\flowClass\Command\TaskFlow;

class TaskService{
	/**
	 * 普通流程通过
	 * 
	 * @param  $config 参数信息
	 * @param  $uid  用户ID
	 */
	public function doTask($config,$uid){
		$command = new TaskFlow();
		return $command->doTask($config,$uid);
	}
	/**
	 * 流程驳回
	 * 
	 * @param  $config 参数信息
	 * @param  $uid  用户ID
	 */
	public function doBack($config,$uid){
		$command = new BackFlow();
		$command->doTask($config,$uid);
	}
	/**
	 * 会签操作
	 * 
	 * @param  $config 参数信息
	 * @param  $uid  用户ID
	 */
	public function doSing($config,$uid){
		$command = new SingFlow();
		$command->doTask($config,$uid);
	}
	
	/**
	 * 普通流程通过
	 * 
	 * @param  $config 参数信息
	 * @param  $uid  用户ID
	 */
	public function doSingEnt($config,$uid,$wf_actionid){
		$command = new SingFlow();
		$command->doSingEnt($config,$uid,$wf_actionid);
	}
}