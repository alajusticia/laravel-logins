<?php

namespace ALajusticia\Logins\Tests\Fakes;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ExceptionHandlerFake implements ExceptionHandler
{
    /**
     * @var array<int, Throwable>
     */
    public array $reported = [];

    public function report(Throwable $e)
    {
        $this->reported[] = $e;
    }

    public function shouldReport(Throwable $e)
    {
        return true;
    }

    public function render($request, Throwable $e)
    {
        return new Response('', 500);
    }

    public function renderForConsole($output, Throwable $e)
    {
        //
    }
}
