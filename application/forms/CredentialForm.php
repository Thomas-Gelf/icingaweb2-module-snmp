<?php

namespace Icinga\Module\Snmp\Forms;

use Icinga\Exception\IcingaException;
use Icinga\Module\Director\Web\Form\QuickForm as DQF;
use Icinga\Module\Snmp\Credential as Obj;
use Icinga\Module\Snmp\Web\Form\QuickForm;

class CredentialForm extends QuickForm
{
    const UNCHANGED_PASSWORD = '___UNCHANGED___';

    protected $db;

    /** @var Obj */
    protected $object;

    public function setup()
    {
        var_dump(($this->object));
        $this->addElement('text', 'name', [
            'label' => $this->translate('Credential name'),
            'required' => true,
            'description' => $this->translate('Identifier for this SNMP credentials')
        ]);
        $this->addElement('select', 'snmp_version', [
            'label'        => $this->translate('SNMP Version'),
            'required'     => true,
            'value'        => '2c',
            'multiOptions' => [
                '3'  => '3',
                '2c' => '2c',
                '1'  => '1',
            ],
            'class' => 'autosubmit'
        ]);
        $this->addElement('text', 'security_name', [
            'label' => $this->translate('Security name'),
            'required' => true,
            'description' => $this->translate('This is your SNMPv3 user')
        ]);
        $this->addElement('checkbox', 'use_auth', [
            'label' => $this->translate('Use authentication'),
            'required' => true,
            'description' => $this->translate('Whether to use an authentication algorithm with a secure passphrase'),
            'class' => 'autosubmit'
        ]);
        $this->addElement('select', 'auth_protocol', [
            'label'        => $this->translate('Authentication protocol'),
            'required'     => true,
            'multiOptions' => [
                null  => t('- Please choose -'),
                'md5' => 'MD5',
                'sha' => 'SHA',
            ],
        ]);
        $this->addElement('password', 'auth_key', [
            'label' => $this->translate('Authentication passphrase'),
            // 'required' => true,
            'placeholder' => '(Unchanged)',
            'description' => $this->translate(
                'This could be a passphrase (should be at least 8 characters long) or a key (0x...)'
            )
        ]);
        $this->addElement('checkbox', 'use_priv', [
            'label' => $this->translate('Use privacy / encryption'),
            'required' => true,
            'description' => $this->translate('Whether to encrypt your SNMP communication'),
            'class' => 'autosubmit'
        ]);
        $this->addElement('select', 'priv_protocol', [
            'label'        => $this->translate('Privacy protocol'),
            'required'     => true,
            'multiOptions' => [
                null  => t('- Please choose -'),
                'des' => 'DES',
                'aes' => 'AES',
            ],
        ]);
        $this->addElement('password', 'priv_key', [
            'label' => $this->translate('Privacy passphrase'),
            'required' => true,
            'renderPassword' => true,
            'description' => $this->translate(
                'This could be a passphrase (should be at least 8 characters long) or a key (0x...)'
            )
        ]);
    }

    protected function fixElements()
    {
        $values = $this->getValues();
        if ($values['snmp_version'] === '3') {
            if (! $values['use_auth']) {
                $this->removeElement('auth_protocol');
                $this->removeElement('auth_key');
                $this->removeElement('priv_protocol');
                $this->removeElement('priv_key');
                $this->removeElement('use_priv');
            }
            if (! $values['use_priv']) {
                $this->removeElement('priv_protocol');
                $this->removeElement('priv_key');
            }
        } else {
            $this->getElement('security_name')
                ->setLabel($this->translate('Community'))
                ->setDescription($this->translate('This is your community string'));
            $this->removeElement('use_auth');
            $this->removeElement('auth_protocol');
            $this->removeElement('auth_key');
            $this->removeElement('use_priv');
            $this->removeElement('priv_protocol');
            $this->removeElement('priv_key');
        }
    }

    public function onRequest()
    {
        $this->fixElements();
    }

    public function onSuccess()
    {
        $values = $this->getValues();

        // These are not to be stored, we just need them for the security_level
        if (isset($values['use_auth'])) {
            $auth = $values['use_auth'];
            unset($values['use_auth']);
        } else {
            $auth = null;
        }

        if (isset($values['use_priv'])) {
            $priv = $values['use_priv'];
            unset($values['use_priv']);
        } else {
            $priv = null;
        }

        if ($values['snmp_version'] === '3') {
            if ($priv && $auth) {
                $values['security_level'] = 'authPriv';
            } elseif ($auth) {
                $values['security_level'] = 'authNoPriv';
            } else {
                $values['security_level'] = 'noAuthNoPriv';
            }
        } else {
            $values['security_level'] = 'noAuthNoPriv';
        }

        switch ($values['security_level']) {
            case 'noAuthNoPriv':
                unset($values['auth_protocol']);
                unset($values['auth_key']);
                // Intentional fall through
            case 'authNoPriv':
                unset($values['priv_protocol']);
                unset($values['priv_key']);
                break;
        }

        if ($priv && $values['auth_key'] === self::UNCHANGED_PASSWORD) {
            if ($this->object) {
                unset($values['auth_key']);
            } else {
                throw new IcingaException('Got invalid AuthKey');
            }
        }

        if ($priv && $values['priv_key'] === self::UNCHANGED_PASSWORD) {
            if ($this->object) {
                unset($values['priv_key']);
            } else {
                throw new IcingaException('Got invalid PrivKey');
            }
        }

        if ($this->object) {
            $this->object->setProperties($values);
            if ($this->object->hasBeenModified()) {
                $this->object->store();
                $this->redirectOnSuccess('Credentials have successfully been stored');
            } else {
                $this->redirectOnSuccess('Credentials have not been changed');
            }
        } else {
            Obj::create($values)->store($this->db);
            $this->redirectOnSuccess('New credentials have been created');
        }
    }

    public function getObject()
    {
        return $this->object;
    }

    public function loadObject($id)
    {
        echo "LOAD";

        $this->object = Obj::load($id, $this->db);
        $this->addHidden('id');
        $props = $this->object->getProperties();
        switch ($props['security_level']) {
            case 'authPriv':
                $props['use_priv'] = 1;
                $props['priv_key'] = self::UNCHANGED_PASSWORD;
            // Intentional fall through
            case 'authNoPriv':
                $props['use_auth'] = 1;
                $props['auth_key'] = self::UNCHANGED_PASSWORD;
                break;
        }
        unset($props['security_level']);
        $this->setDefaults($props);
        return $this;
    }

    public function setDb($db)
    {
        $this->db = $db;
        return $this;
    }
}
