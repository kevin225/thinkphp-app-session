<?php
namespace mwtech\thinkphp_app_session\model;

use think\Model;

class ProjectUserBaseInfoModel extends Model
{
    protected $name = "project_user_base_info";  // 默认会是project_user_model，所以需要指定

    /**
     * 当前用户邀请的用户，也就是通过当前用户分享链接进入H5并注册的用户
     */
    public function inviteUsers()
    {
        return $this->hasMany(ProjectUserBaseInfoModel::class,'invite_from_user_id','id');
    }

    public function inviter()
    {
        return $this->belongsTo(ProjectUserBaseInfoModel::class,'invite_from_user_id','id');
    }

}
