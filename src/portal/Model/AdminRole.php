<?php
namespace Portal\Model;

use PhalApi\Model\DataModel;

class AdminRole extends DataModel {

    public function getTableName($id) {
        return 'portal_admin_role';
    }
}
