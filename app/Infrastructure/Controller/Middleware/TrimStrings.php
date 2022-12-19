<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /**
     * Имена атрибутов, которые не должны преобразовываться.
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
    ];
}
