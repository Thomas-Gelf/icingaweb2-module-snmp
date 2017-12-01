<?php

namespace Icinga\Module\Snmp\Controllers;

use dipl\Html\Html;
use dipl\Html\Icon;
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
            ->setDb($this->db());
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
            $dependencies->addNameValueRow(
                Html::tag('strong', null, 'MIB'),
                Html::tag('strong', null, 'Imported Objects')
            );
            foreach ($parsed->imports as $import => $objects) {
                $dependencies->addNameValueRow($import, implode(', ', $objects));
            }
        }
        $this->content()->add([
            $dependencies,
            Html::tag('h2', null, 'MIB Tree'),
            MibParser::getHtmlTreeFromParsedMib($parsed),
        ])->addAttributes(['class' => 'icinga-module module-director']);
    }

    public function uploadsAction()
    {
        $this->setAutorefreshInterval(1);
        $this->setSnmpTabs()->activate('mib_uploads');
        $this->addTitle('Upload your MIB files');
        $this->actions()->add(
            Link::create($this->translate('Add'), 'snmp/mib/upload', null, [
                'class' => 'icon-plus',
                'data-base-target' => '_next'
            ])
        );

        $table = new MibUploadsTable($this->db());
        if (count($table)) {
            $table->renderTo($this);
        } else {
            $this->content()
                ->add(Icon::create('ok'))
                ->add('There are no pending MIB files in our queue');
        }
    }
}
