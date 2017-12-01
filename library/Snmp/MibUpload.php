<?php

namespace Icinga\Module\Snmp;

use Icinga\Module\Director\Data\Db\DbObject;

class MibUpload extends DbObject
{
    protected $table = 'mib_upload';

    protected $keyName = 'id';

    protected $autoincKeyName = 'id';

    protected $defaultProperties = [
        'id'                => null,
        'username'          => null,
        'client_ip'         => null,
        'mib_name'          => null,
        'imports_from'      => null,
        'original_filename' => null,
        'raw_mib_file'      => null,
        'parsed_mib'        => null,
    ];
}
