<?php
namespace App\Command;

use App\JiraRepository;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'jira:timespent')]
class GetTimeSpent extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = require __DIR__ . '/../../config.php';

        $jira = new JiraRepository($config);
        $sprintStart = Carbon::now()->subDays(2)->startOfWeek(Carbon::MONDAY);

        $issues = collect($jira->getCurrentSprintIssues())->keyBy('key');

        $userWorklogs = collect($issues)
            ->pluck('fields.worklog.worklogs', 'key')
            ->filter()
            ->flatMap(fn($wls, $key) => collect($wls)
                ->map(fn($wl) => (object)[
                    'issue' => $issues[$key],
                    'started' => new Carbon($wl->started),
                    'time' => CarbonInterval::seconds($wl->timeSpentSeconds),
                    'worker' => $wl->author->displayName,
                ])
            )
            ->filter(fn($wl) => $wl->started > $sprintStart)
            ->sortBy('started')
            ->groupBy('worker');


        $table = new Table($output);

        foreach($userWorklogs as $name => $worklogs) {
            $time = CarbonInterval::seconds($worklogs->sum('time.totalSeconds'));

            $table->addRow(new TableSeparator());
            $table->addRow(["<info>$name</info>", "<info>$time->totalHours</info>", '', '']);
            $table->addRow(new TableSeparator());


            foreach ($worklogs as $worklog) {
                $table->addRow([
                    $worklog->started->dayName,
                    $worklog->issue->key,
                    $worklog->time->totalHours,
                    substr($worklog->issue->fields->summary, 0, 50)
                ]);
            }
        }

        $table->render();

        return Command::SUCCESS;
    }
}
