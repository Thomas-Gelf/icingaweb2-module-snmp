<?php

namespace Icinga\Module\Snmp\Controllers;

use dipl\Html\Link;
use Icinga\Module\Snmp\ActionController;
use Icinga\Module\Snmp\Web\Table\CredentialsTable;

class CredentialsController extends ActionController
{
    public function indexAction()
    {
        $this->setSnmpTabs()->activate('credentials');
        $this->addTitle($this->translate('SNMP credentials'));
        $this->actions()->add(
            Link::create($this->translate('Add'), 'snmp/credential', null, [
                'class' => 'icon-plus',
                'data-base-target' => '_next'
            ])
        );
        (new CredentialsTable($this->db()))->renderTo($this);
    }
}
