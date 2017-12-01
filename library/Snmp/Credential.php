<?php

namespace Icinga\Module\Snmp;

use Icinga\Module\Director\Data\Db\DbObject;

class Credential extends DbObject
{
    protected $table = 'snmp_credential';

    protected $keyName = 'id';

    protected $autoincKeyName = 'id';

    protected $defaultProperties = array(
        'id'             => null,
        'name'           => null,
        'snmp_version'   => null,
        'security_name'  => null,
        'security_level' => null,
        'auth_protocol'  => null,
        'auth_key'       => null,
        'priv_protocol'  => null,
        'priv_key'       => null,
    );
}
