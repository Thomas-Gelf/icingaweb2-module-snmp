<?php

namespace Icinga\Module\Snmp\Forms;

use Exception;
use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use Icinga\Exception\IcingaException;
use Icinga\Module\Director\Web\Form\QuickForm;
use Icinga\Module\Snmp\MibParser;
use Icinga\Module\Snmp\MibUpload;
use Icinga\Web\Notification;

class MibForm extends QuickForm
{
    private $db;

    private $failed = false;

    private $fileCount = 0;

    public function setup()
    {
        $this->setAttrib('enctype', 'multipart/form-data');

        require_once Icinga::app()
            ->getModuleManager()
            ->getModule('director')
            ->getApplicationDir() . '/views/helpers/FormSimpleNote.php';
        $this->addHtml('<div class="mib-drop-zone"></div>');

        $this->addElement('file', 'uploaded_file', [
            'label'       => $this->translate('Choose file'),
            'destination' => $this->getTempDir(),
            'valueDisabled' => true,
            'isArray'       => true,
            'multiple'      => true,
        ]);

        $this->setSubmitLabel($this->translate('Upload'));
    }

    protected function getTempDir()
    {
        return sys_get_temp_dir();
    }

    protected function processUploadedSource()
    {
        if (! array_key_exists('uploaded_file', $_FILES)) {
            throw new IcingaException('Got no file');
        }

        foreach ($_FILES['uploaded_file']['tmp_name'] as $key => $tmpFile) {
            if (! is_uploaded_file($tmpFile)) {
                continue;
            }
            $originalFilename = $_FILES['uploaded_file']['name'][$key];

            $source = file_get_contents($tmpFile);
            unlink($tmpFile);
            try {
                $parsed = MibParser::parseString($source);
            } catch (Exception $e) {
                $this->addError($originalFilename . ' failed: ' . $e->getMessage());
                Notification::error($originalFilename . ' failed: ' . $e->getMessage());
                $this->failed = true;
                continue;
            }
            if (empty($parsed)) {
                Notification::error('Could not parse ' . $originalFilename);
                $this->addError('Could not parse ' . $originalFilename);
                $this->failed = true;
                continue;
            }

            $this->fileCount++;
            // $this->addError(' Missing: ' . implode(', ', array_keys((array) $parsed->imports)));
            MibUpload::create([
                'username' => Auth::getInstance()->getUser()->getUsername(),
                'client_ip' => $_SERVER['REMOTE_ADDR'],
                'mib_name'          => $parsed->name,
                'imports_from'      => json_encode(array_keys((array) $parsed->imports)),
                'original_filename' => $originalFilename,
                'raw_mib_file'      => $source,
                'parsed_mib'        => json_encode($parsed),
            ])->store($this->db);
        }

        return true;
    }

    public function onRequest()
    {
        if ($this->hasBeenSent()) {
            try {
                $this->processUploadedSource();
            } catch (Exception $e) {
                $this->addError($e->getMessage());
                return;
            }

            if ($this->fileCount > 0) {
                if ($this->fileCount === 1) {
                    $this->redirectOnSuccess('New MIB file has been enqueued');
                } else {
                    $this->redirectOnSuccess(sprintf('%d MIB files have been enqueued', $this->fileCount));
                }
            }
        }
    }

    public function onSuccess()
    {
    }

    public function setDb($db)
    {
        $this->db = $db;
        return $this;
    }

    protected function invalidProcess()
    {
        /** @var \Zend_Form_Element_File $el */
        /*
        $el = $this->getElement('uploaded_file');
        $originalFilename = $el->getValue();

        if ($el && $this->hasBeenSent()) {
            $tmpDir = $this->getTempDir();
            $tmpFile = tempnam($tmpDir, 'mibupload_');

            // TODO: race condition, try to do this without unlinking here
            unlink($tmpFile);

            $el->addFilter('Rename', $tmpFile);
            if ($el->receive()) {
                var_dump($tmpFile);
                exit;
                if (! MibParser::preValidateFile($tmpFile)) {
                    throw new IcingaException(
                        'MIB file validation failed: %s',
                        str_replace($tmpFile, $originalFilename, MibParser::getLastValidationError())
                    );
                }
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
        */
    }
}
