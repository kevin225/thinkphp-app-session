<?php
namespace mwtech\thinkphp_app_session\model;

interface IUserModel
{
    public function initialUser();

    public function findById($uid);
}