<?php

declare(strict_types=1);

use App\Domain\Booking\AppointmentCreator;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$input = json_decode(base64_decode((string) ($argv[1] ?? ''), true), true, 512, JSON_THROW_ON_ERROR);
$key = (string) ($argv[2] ?? '');
Carbon::setTestNow((string) ($argv[3] ?? ''));

try {
    $result = $app->make(AppointmentCreator::class)->create($input, $key);
    fwrite(STDOUT, json_encode($result, JSON_THROW_ON_ERROR));
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage());
    exit(1);
}
