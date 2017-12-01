<?php

namespace Icinga\Module\Snmp;

use Icinga\Exception\IcingaException;
use Icinga\Module\Snmp\Web\Tree\MibTreeRenderer;
use React\ChildProcess\Process;
use React\EventLoop\Factory as Loop;

class MibParser
{
    protected static $lastValidationErrors;

    public static function preValidateFile($filename)
    {
        $binary = '/usr/bin/smilint';
        if (! file_exists($binary)) {
            throw new IcingaException('%s not found', $binary);
        }

        $command = sprintf(
            "exec %s '%s' -l 2",
            $binary,
            escapeshellarg($filename)
        );

        $loop = Loop::create();
        $process = new Process($command);
        $process->start($loop);
        $buffer = '';
        $timer = $loop->addTimer(10, function () use ($process) {
            $process->terminate();
        });
        $process->stdout->on('data', function ($string) use (& $buffer) {
            $buffer .= $string;
        });
        $process->stderr->on('data', function ($string) use (& $buffer) {
            $buffer .= $string;
        });
        $process->on('exit', function ($exitCode, $termSignal) use ($timer) {
            $timer->cancel();
            if ($exitCode === null) {
                if ($termSignal === null) {
                    throw new IcingaException(
                        'Fuck, I have no idea how the validator got killed'
                    );
                } else {
                    throw new IcingaException(
                        "They killed the validator with $termSignal"
                    );
                }
            } else {
                if ($exitCode !== 0) {
                    throw new IcingaException("Validator exited with $exitCode");
                }
            }
        });

        $loop->run();

        if (empty($buffer)) {
            return true;
        } else {
            self::$lastValidationErrors = $buffer;

            return false;
        }
    }

    public static function getLastValidationError()
    {
        return self::$lastValidationErrors;
    }

    public static function parseString($string)
    {
        $loop = Loop::create();
        $process = new Process(static::getCommandString());
        $process->start($loop);
        $buffer = '';
        $timer = $loop->addTimer(10, function () use ($process) {
            $process->terminate();
        });
        $process->stdout->on('data', function ($string) use (& $buffer) {
            $buffer .= $string;
        });
        $errBuffer = '';
        $process->stderr->on('data', function ($string) use (& $errBuffer) {
            $errBuffer .= $string;
        });
        $process->on('exit', function ($exitCode, $termSignal) use ($buffer, $errBuffer, $timer) {
            $timer->cancel();
            $out = [];
            if (! empty($buffer)) {
                $out[] = "STDOUT: $buffer";
            }
            if (! empty($errBuffer)) {
                $out[] = "STDERR: $errBuffer";
            }
            if (empty($out)) {
                $out = '';
            } else {
                $out = ': ' . implode(', ', $out);
            }
            if ($exitCode === null) {
                if ($termSignal === null) {
                    throw new IcingaException(
                        'Fuck, I have no idea how the parser got killed'
                    );
                } else {
                    throw new IcingaException(
                        "They killed the parser with $termSignal$out"
                    );
                }
            } else {
                if ($exitCode !== 0) {
                    throw new IcingaException("Parser exited with $exitCode$out");
                }
            }
        });

        $process->stdin->write("$string\n");

        $loop->run();
        return json_decode($buffer);
    }

    public static function getHtmlTreeFromParsedMib($parsedMib)
    {
        $root = null;
        $clone = [];
        $seen = [];
        $tree = $parsedMib->tree;
        if (empty($tree)) {
            return null;
        }

        foreach ($tree as $key => $members) {
            foreach ($members as $id => $member) {
                if (property_exists($tree, $member)) {
                    $seen[$member] = $member;
                }
            }
        }

        foreach ($tree as $key => $members) {
            if (! array_key_exists($key, $seen)) {
                $root = $key;
            }
        }

        if ($root === null) {
            throw new IcingaException('Got no root node');
        }

        $clone[$root] = ['name' => $root, 'children' => [], 'path' => ".$root", 'oid' => ".$root"];
        static::getMembers($clone[$root]['children'], $tree->$root, $tree, ".$root", ".$root");

        return new MibTreeRenderer($clone[$root]);
    }

    protected static function getMembers(& $clone, $subTree, $tree, $namePath, $oidPath)
    {
        foreach ($subTree as $id => $key) {
            $oid = "$oidPath.$id";
            $names = "$namePath.$key";
            if (property_exists($tree, $key)) {
                $clone[$key] = [
                    'name'     => $key,
                    'oid'      => $oid,
                    'path'     => $names,
                    'children' => []
                ];

                static::getMembers($clone[$key]['children'], $tree->$key, $tree, $names, $oid);
            } else {
                $clone[$key] = [
                    'name'     => $key,
                    'oid'      => $oid,
                    'path'     => $names,
                ];
            }
        }
    }

    protected static function getCommandString()
    {
        return 'exec ' . dirname(dirname(__DIR__)) . '/contrib/mib-parser.pl';
    }

    public static function parseFile($file)
    {
        return static::parseString(file_get_contents($file));
    }
}
