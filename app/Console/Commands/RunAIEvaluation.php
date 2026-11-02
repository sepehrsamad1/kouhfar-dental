<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AssistantEvaluationRunner;
use Illuminate\Console\Command;

final class RunAIEvaluation extends Command
{
    protected $signature = 'ai:evaluate';

    protected $description = 'Run the versioned AI assistant release evaluation and safety gates';

    public function handle(AssistantEvaluationRunner $runner): int
    {
        $report = $runner->run();
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $report['passed'] === true ? self::SUCCESS : self::FAILURE;
    }
}
