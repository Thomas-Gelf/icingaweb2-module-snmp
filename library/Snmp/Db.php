<?php

namespace Icinga\Module\Snmp;

use Icinga\Module\Director\Data\Db\DbConnection;

class Db extends DbConnection
{
    protected $modules = array();

    protected function db()
    {
        return $this->getDbAdapter();
    }
}
