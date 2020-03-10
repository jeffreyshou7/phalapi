<?php

namespace Portal\Api;

use PhalApi\Api;
use Portal\Domain\Admin as AdminDomain;
use PhalApi\Exception\InternalServerErrorException;

/**
 * 管理员模块
 * @author dogstar 20200307
 */
class Admin extends Api {
    
    public function getRules() {
        return array(
            'login' => array(
                'username' => array('name' => 'username', 'require' => true, 'desc' => '账号'),
                'password' => array('name' => 'password', 'require' => true, 'desc' => '密码'),
            ),
            'alterPassword'=> array(
                'oldPassword' => array('name' => 'old_password', 'require' => true, 'desc' => '旧密码'),
                'newPassword' => array('name' => 'new_password', 'require' => true, 'desc' => '新密码'),
            ),
            'install' => array(
                'username' => array('name' => 'username', 'require' => true, 'desc' => '账号'),
                'password' => array('name' => 'password', 'require' => true, 'desc' => '密码'),
            ),
        );
    }

    /**
     * 管理员登录
     * @desc 根据管理员账号和密码，进行登录
     */
    public function login() {
        $rs = array('is_login' => false);

        $domain = new AdminDomain();
        $rs['is_login'] = $domain->login($this->username, $this->password);
        
        return $rs;
    }

    /**
     * 检测登录
     * @desc 检测管理员是否登录，以及运营平台是否已经升级
     * @ignore
     */
    public function checkLogin() {
        $rs = array('is_login' => false, 'is_install' => true);
        
        $rows = \PhalApi\DI()->notorm->portal->queryAll("show tables like '%phalapi_portal_admin'");
        if (empty($rows[0])) {
            $rs['is_install'] = false;
        } else {
            $rs['is_login'] = \PhalApi\DI()->admin->check(false);
        }
        
        return $rs;
    }
    
    /**
     * 安装
     * @desc 首次安装，安装后可以把此接口永久关闭
     */
    public function install() {
        // 创建数据库表
        $sql = file_get_contents(API_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'phalapi.sql');
        $sqlArr = explode(";\n", $sql);
        foreach ($sqlArr as $sqlOne) {
            $sqlOne = trim($sqlOne);
            if (empty($sqlOne)) {
                continue;
            }
            //var_dump($sqlOne);
            try {
                \PhalApi\DI()->notorm->portal->executeSql($sqlOne);
            } catch (\PDOException $ex) {
                if (stripos($ex->getMessage(), 'already exists')) {
                    continue;
                }
                throw new InternalServerErrorException($ex->getMessage());
            }
        }
        
        // 添加管理员
        $domain = new AdminDomain();
        $domain->createAdmin($this->username, $this->password, AdminDomain::ADMIN_ROLE_SUPERMAN);
        
        return array();
    }
    
    /**
     * 修改密码
     * @desc 修改管理员自己的密码
     */
    public function alterPassword() {
        $rs = array('is_alter' => false);

        \PhalApi\DI()->admin->check();

        $domain = new AdminDomain();
        $rs['is_alter'] = $domain->alterPassword($this->oldPassword, $this->newPassword);

        return $rs;
    }

    /**
     * 退出管理员登录
     * @desc 退出当前管理的登录
     */
    public function logout() {
        \PhalApi\DI()->admin->logout();
        return array('is_logout' => true);
    }
}
