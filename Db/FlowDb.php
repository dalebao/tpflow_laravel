<?php
/**
 *+------------------
 * 流信息处理
 *+------------------
 */

namespace App\Extend\Workflow\Db;

use Illuminate\Support\Facades\DB;

class FlowDb
{
    /**
     * 获取类别工作流
     *
     * @param $wf_type
     */
    public static function getWorkflowByType($wf_type)
    {
        $workflow = array();
        if ($wf_type == '') {
            return $workflow;
        }
        $info = DB::connection('workflow')
            ->table('flow')->where('is_del', '=', 0)->where('status', '=', 0)
            ->where('type', '=', $wf_type)->select()->get()->toArray();
        return $info;
    }

    /**
     * 获取流程信息
     *
     * @param $fid
     */
    public static function GetFlowInfo($fid)
    {
        if ($fid == '') {
            return false;
        }
        $info = (array)DB::connection('workflow')->table('flow')->find($fid);
        if ($info) {
            return $info['flow_name'];
        } else {
            return false;
        }
    }

    /**
     * 判断工作流是否存在
     *
     * @param $wf_id
     */
    public static function getWorkflow($wf_id)
    {
        if ($wf_id == '') {
            return false;
        }
        $info = (array)DB::connection('workflow')->table('flow')->find($wf_id);
        if ($info) {
            return $info;
        } else {
            return false;
        }
    }

    /**
     * 获取步骤信息
     *
     * @param $id
     */
    public static function getflowprocess($id)
    {
        if ($id == '') {
            return false;
        }
        $info = (array)DB::connection('workflow')->table('flow_process')->find($id);
        if ($info) {
            return $info;
        } else {
            return false;
        }
    }
}