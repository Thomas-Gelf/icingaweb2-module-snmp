<?php

namespace Icinga\Module\Snmp;

use dipl\Web\CompatController;
use dipl\Web\Widget\Tabs;
use Icinga\Module\Snmp\Web\Form\FormLoader;

abstract class ActionController extends CompatController
{
    /** @var Db */
    protected $db;

    public function loadForm($name)
    {
        $loader = new FormLoader();
        return $loader->load($name, $this->Module());
    }

    /**
     * @return Tabs
     */
    protected function setSnmpTabs()
    {
        return $this->tabs()->add('agents', [
            'title' => $this->translate('Agents'),
            'url'   => 'snmp/agents'
        ])->add('credentials', [
            'title' => $this->translate('Credentials'),
            'url'   => 'snmp/credentials'
        ])->add('mibs', [
            'title' => $this->translate('MIBs'),
            'url'   => 'snmp/mibs'
        ])->add('mib_uploads', [
            'title' => $this->translate('MIB Uploads'),
            'url'   => 'snmp/mib/uploads'
        ]);
    }

    /**
     * @return Db
     */
    protected function db()
    {
        if ($this->db === null) {
            $this->db = Db::fromResourceName($this->Config()->get('db', 'resource'));
        }

        return $this->db;
    }
}
