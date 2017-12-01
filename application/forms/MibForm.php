<?php

namespace Icinga\Module\Snmp\Forms;

use Exception;
use Icinga\Authentication\Auth;
use Icinga\Module\Director\Web\Form\QuickForm;
use Icinga\Module\Snmp\MibParser;
use Icinga\Module\Snmp\MibUpload;

class MibForm extends QuickForm
{
    protected $db;

    protected $mibSource;

    protected $parsedMib;

    public function setup()
    {
        $this->setAttrib('enctype', 'multipart/form-data');

        $this->addElement('file', 'uploaded_file', [
            'label'       => $this->translate('File'),
            'destination' => $this->getTempDir(),
            'required'    => true,
        ]);

        /** @var \Zend_Form_Element_File $el */
        $el = $this->getElement('uploaded_file');
        $el->setValueDisabled(true);

        $this->setSubmitLabel($this->translate('Upload'));
    }

    protected function getTempDir()
    {
        return sys_get_temp_dir();
    }

    protected function processUploadedSource()
    {
        /** @var \Zend_Form_Element_File $el */
        $el = $this->getElement('uploaded_file');
        $originalFilename = $el->getValue();

        if ($el && $this->hasBeenSent()) {
            $tmpDir = $this->getTempDir();
            $tmpFile = tempnam($tmpDir, 'mibupload_');

            // TODO: race condition, try to do this without unlinking here
            unlink($tmpFile);

            $el->addFilter('Rename', $tmpFile);
            if ($el->receive()) {
                $source = file_get_contents($tmpFile);
                unlink($tmpFile);
                $parsed = MibParser::parseString($source);
                $this->addError(' Missing: ' . implode(', ', array_keys((array) $parsed->imports)));
                MibUpload::create([
                    'username' => Auth::getInstance()->getUser()->getUsername(),
                    'client_ip' => $_SERVER['REMOTE_ADDR'],
                    'mib_name'          => $parsed->name,
                    'imports_from'      => json_encode(array_keys((array) $parsed->imports)),
                    'original_filename' => $originalFilename,
                    'raw_mib_file'      => $source,
                    'parsed_mib'        => json_encode($parsed),
                ])->store($this->db);
            } else {
                // foreach ($el->file->getMessages() as $error) {
                foreach ($el->getMessages() as $error) {
                    $this->addError($error);
                }
            }
        }

        return $this;
    }

    public function onSuccess()
    {
        try {
            $this->processUploadedSource();
        } catch (Exception $e) {
            $this->addError($e->getMessage());

            return;
        }
        $this->redirectOnSuccess('New MIB file has been enqueued');
    }

    public function setDb($db)
    {
        $this->db = $db;
        return $this;
    }
}
