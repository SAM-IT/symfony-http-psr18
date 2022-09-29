<?php

declare(strict_types=1);

namespace SamIt\SymfonyHttpPsr18;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TransportException extends \Exception implements TransportExceptionInterface
{
}
