<?php

namespace Icinga\Module\Snmp\Web\Table;

use dipl\Html\Link;
use dipl\Web\Table\ZfQueryBasedTable;

class CredentialsTable extends ZfQueryBasedTable
{
    protected $searchColumns = [
        'name',
        'security_name'
    ];

    public function getColumnsToBeRendered()
    {
        return array(
            $this->translate('Credential name'),
            $this->translate('SNMP version'),
            $this->translate('Authentication'),
            $this->translate('Encryption'),
        );
    }

    public function renderRow($row)
    {
        return static::row([
            Link::create($row->name, 'snmp/credential', ['id' => $row->id]),
            $row->snmp_version,
            $row->security_level !== 'noAuthNoPriv' ? strtoupper($row->auth_protocol) : '-',
            $row->security_level === 'authPriv' ? strtoupper($row->priv_protocol) : '-'
        ]);
    }

    public function prepareQuery()
    {
        return $this->db()->select()
            ->from('snmp_credential', [
                'id',
                'name',
                'snmp_version',
                'security_level',
                'auth_protocol',
                'priv_protocol',
            ])->order('name');
    }
}
