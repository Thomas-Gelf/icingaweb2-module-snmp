<?php

namespace Icinga\Module\Snmp\Web\Tree;

use dipl\Html\BaseElement;
use dipl\Html\Html;
use dipl\Html\Link;

class MibTreeRenderer extends BaseElement
{
    protected $tag = 'ul';

    protected $defaultAttributes = [
        'class'            => 'tree',
        'data-base-target' => '_next',
    ];

    protected $tree;

    public function __construct($tree)
    {
        $this->tree = $tree;
    }

    public function renderContent()
    {
        $this->add(
            $this->dumpTree($this->tree)
        );

        return parent::renderContent();
    }

    protected function dumpTree($tree, $level = 0)
    {
        $hasChildren = ! empty($tree['children']);
//        $type = $this->tree->getType();
        $type = 'service';

        $li = Html::tag('li');
        if (! $hasChildren) {
            $li->attributes()->add('class', 'collapsed');
        }

        if ($hasChildren) {
            $li->add(Html::tag('span', ['class' => 'handle']));
        }

        $title = sprintf('%s (%s)', $tree['path'], $tree['oid']);
        if ($level === 0) {
            $li->add(Html::tag('a', [
                'name'  => $tree['name'],
                'class' => 'icon-globe',
                'title' => $title,
            ], $tree['name']));
        } else {
            $li->add(Link::create(
                $tree['name'],
                "snmp/mib/object",
                array('name' => $tree['name']),
                array(
                    'class' => 'icon-' .$type,
                    'title' => $title,
                )
            ));
        }

        if ($hasChildren) {
            $li->add(
                $ul = Html::tag('ul')
            );
            foreach ($tree['children'] as $child) {
                $ul->add($this->dumpTree($child, $level + 1));
            }
        }

        return $li;
    }
}
