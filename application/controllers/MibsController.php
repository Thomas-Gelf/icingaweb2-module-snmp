<?php

namespace Icinga\Module\Snmp\Controllers;

use dipl\Html\Link;
use Icinga\Module\Snmp\ActionController;

class MibsController extends ActionController
{
    public function indexAction()
    {
        $this->setSnmpTabs()->activate('mibs');
        $this->addTitle($this->translate('SNMP MIBs'));
        $this->actions()->add(
            Link::create($this->translate('Add'), 'snmp/mib', null, [
                'class' => 'icon-plus',
                'data-base-target' => '_next'
            ])
        );
    }
}
