<?php

namespace Icinga\Module\Snmp\Web\Table;

use dipl\Html\Link;
use dipl\Web\Table\ZfQueryBasedTable;

class MibUploadsTable extends ZfQueryBasedTable
{
    protected $searchColumns = [
        'mib_name',
        'username'
    ];

    public function getColumnsToBeRendered()
    {
        return array(
            $this->translate('MIB name'),
            $this->translate('Uploader'),
        );
    }

    public function renderRow($row)
    {
        return static::row([
            Link::create($row->mib_name, 'snmp/mib/process', ['id' => $row->id]),
            sprintf('%s (%s)', $row->username, $row->client_ip)
        ]);
    }

    public function prepareQuery()
    {
        return $this->db()->select()
            ->from('mib_upload', [
                'id',
                'mib_name',
                'client_ip',
                'username',
            ])->order('mib_name');
    }
}
