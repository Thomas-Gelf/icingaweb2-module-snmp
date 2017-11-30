<?php

namespace Icinga\Module\Snmp\Controllers;

use Icinga\Module\Snmp\ActionController;
use Icinga\Module\Snmp\Forms\CredentialForm;

class CredentialController extends ActionController
{
    public function indexAction()
    {
        $form = (new CredentialForm())
//        $form = $this->loadForm('credential')
            ->setDb($this->db())
            ->setSuccessUrl('snmp/list/credentials');
        if ($id = $this->params->get('id')) {
            $form->loadObject($id);
            $this->addSingleTab('Modify');
            $this->addTitle($this->translate('Modify SNMP credential'));
        } else {
            $this->addSingleTab('Add');
            $this->addTitle($this->translate('Add SNMP credential'));
        }

        $this->content()->add($form->handleRequest())
            ->addAttributes(['class' => 'icinga-module module-director']);
    }
}
