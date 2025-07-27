<?php

namespace MohamadRZ\NovaPerms\storage\implementations\file;

use RuntimeException;
use Throwable;

class FileIOException extends RuntimeException
{
    public function __construct(string $fileName, Throwable $cause = null)
    {
        $message = "Exception thrown while reading/writing file: " . $fileName;
        parent::__construct($message, 0, $cause);
    }
}