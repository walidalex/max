<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Support\Logger;
use Throwable;

final class ExceptionHandler
{
    public function __construct(private readonly Logger $logger, private readonly bool $debug) {}
    public function render(Throwable $exception, Request $request): Response
    {
        $reference = bin2hex(random_bytes(6));
        $this->logger->error('Unhandled exception', ['reference' => $reference, 'exception' => $exception]);
        $message = $this->debug ? $exception->getMessage() : 'حدث خطأ غير متوقع.';
        if ($request->expectsJson()) { return Response::json(['message' => $message, 'reference' => $reference], 500); }
        $details = $this->debug ? '<pre class="text-start" dir="ltr">' . e($exception->__toString()) . '</pre>' : '';
        return Response::html('<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><title>خطأ</title><body><h1>تعذر إكمال الطلب</h1><p>' . e($message) . '</p><small>' . e($reference) . '</small>' . $details . '</body></html>', 500);
    }
}
