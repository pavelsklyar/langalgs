<?php

declare(strict_types=1);

namespace App\Model\Language;

use App\Model\Language\Console\CalcLangCommand;
use Illuminate\Support\ServiceProvider;

final class LanguageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            CalcLangCommand::class,
        ]);
    }
}
