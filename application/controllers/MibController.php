<?php

namespace Icinga\Module\Snmp\Controllers;

use dipl\Html\Link;
use dipl\Web\Widget\NameValueTable;
use Icinga\Module\Snmp\ActionController;
use Icinga\Module\Snmp\Forms\MibForm;
use Icinga\Module\Snmp\MibParser;
use Icinga\Module\Snmp\MibUpload;
use Icinga\Module\Snmp\Web\Table\MibUploadsTable;

class MibController extends ActionController
{
    public function uploadAction()
    {
        $form = (new MibForm())
            ->setDb($this->db())
            ->setSuccessUrl('snmp/mib/uploads');
            $this->addSingleTab('Add');
            $this->addTitle($this->translate('Add SNMP MIB file'));

        $this->content()->add($form->handleRequest())
            ->addAttributes(['class' => 'icinga-module module-director']);
    }

    public function processAction()
    {
        $this->addSingleTab('Process MIB');
        $this->addTitle($this->translate('Process uploaded MIB file'));
        $id = $this->params->getRequired('id');
        $upload = MibUpload::load($id, $this->db());
        $parsed = json_decode($upload->get('parsed_mib'));

        $dependencies = new NameValueTable();
        if (empty($parsed->imports)) {
            $dependencies->addNameValueRow('-', 'This MIB has no IMPORTS / dependencies');
        } else {
            foreach ($parsed->imports as $import => $objects) {
                $dependencies->addNameValueRow($import, implode(', ' ,$objects));
            }
        }
        $this->content()->add([
            $dependencies,
            MibParser::getHtmlTreeFromParsedMib($parsed),
        ])->addAttributes(['class' => 'icinga-module module-director']);
    }

    public function uploadsAction()
    {
        $this->setSnmpTabs()->activate('mib_uploads');
        $this->addTitle('MIB file uploads');
        $this->actions()->add(
            Link::create($this->translate('Add'), 'snmp/mib/upload', null, [
                'class' => 'icon-plus',
                'data-base-target' => '_next'
            ])
        );

        (new MibUploadsTable($this->db()))->renderTo($this);
    }
}
