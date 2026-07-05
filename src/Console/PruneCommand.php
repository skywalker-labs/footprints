<?php

declare(strict_types=1);

namespace Skywalker\Footprints\Console;

use Illuminate\Console\Command;
use Skywalker\Footprints\Visit;

class PruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'footprints:prune {--days= : The number of days to retain unassigned Footprints data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune stale (ie unassigned) entries from the Footprints database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $duration = config('footprints.attribution_duration');
        $duration = is_numeric($duration) ? (int) $duration : 2592000;
        
        $daysOpt = $this->option('days');
        $days = (int) ($daysOpt ?? ($duration / (60 * 60 * 24)));

        Visit::query()->prunable($days)->delete();

        $this->info("Successfully pruned unassigned footprints older than {$days} days.");

        return self::SUCCESS;
    }
}
