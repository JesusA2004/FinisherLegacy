<?php

namespace App\Console\Commands;

use App\Actions\Athletes\EnsureAthleteForUser;
use App\Actions\Athletes\LinkParticipantToAthlete;
use App\Actions\Athletes\ResolveAthleteIdentity;
use App\Enums\AthleteIdentityConflictStatus;
use App\Enums\ResolveAthleteIdentityStatus;
use App\Models\Athlete;
use App\Models\EventParticipant;
use App\Models\User;
use App\Services\Athletes\AthleteIdentityMatcher;
use App\Support\Athletes\AthleteIdentityCandidateData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time (repeatable, idempotent) pass over existing data to backfill
 * Athlete records for everything Slice 3 shipped after there was already
 * data in the system — see docs/adr/0004-athlete-canonical-identity.md
 * §Backfill. Priority order (§45): (1) Users with the athlete role,
 * (2) EventParticipant.user_id already known, (3-5) remaining participants
 * resolved through the same matcher as everything else (email exact,
 * unique name, or a recorded conflict) — never a separate, more
 * aggressive matching pass.
 */
class BackfillAthletes extends Command
{
    protected $signature = 'finisher:backfill-athletes {--dry-run} {--apply}';

    protected $description = 'Backfill canonical Athlete records for existing Users/EventParticipants — idempotent, dry-run by default.';

    /** @var array<string, int> */
    private array $report = [
        'users_processed' => 0,
        'athletes_created' => 0,
        'participants_linked' => 0,
        'exact_matches' => 0,
        'conflicts' => 0,
        'insufficient_data' => 0,
        'possible_duplicates' => 0,
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $dryRun = (bool) $this->option('dry-run');

        if (! $apply && ! $dryRun) {
            $this->error('Especifica --dry-run (solo reporte) o --apply (escribe cambios).');

            return self::FAILURE;
        }

        $this->info($apply ? 'Ejecutando backfill (--apply)...' : 'Simulando backfill (--dry-run, no se escribe nada)...');

        if ($apply) {
            DB::transaction(fn () => $this->runBackfill(true));
        } else {
            $this->runBackfill(false);
        }

        $this->printReport();

        return self::SUCCESS;
    }

    private function runBackfill(bool $apply): void
    {
        $this->backfillUsersWithAthleteRole($apply);
        $this->backfillParticipantsWithKnownUser($apply);
        $this->backfillRemainingParticipants($apply);
    }

    /**
     * Priority 1 (§45).
     */
    private function backfillUsersWithAthleteRole(bool $apply): void
    {
        User::role('athlete')->whereDoesntHave('athlete')->chunkById(200, function ($users) use ($apply) {
            foreach ($users as $user) {
                $this->report['users_processed']++;

                if (! $apply) {
                    continue;
                }

                $before = Athlete::count();
                app(EnsureAthleteForUser::class)->handle($user, 'backfill');
                $this->report[Athlete::count() > $before ? 'athletes_created' : 'exact_matches']++;
            }
        });
    }

    /**
     * Priority 2 (§45) — a participation already has a known account, so
     * its Athlete is simply the account's Athlete (creating one first if
     * that account still doesn't have one, e.g. it predates the athlete
     * role check above having run for it).
     */
    private function backfillParticipantsWithKnownUser(bool $apply): void
    {
        EventParticipant::query()
            ->whereNotNull('user_id')
            ->whereNull('athlete_id')
            ->with('user')
            ->chunkById(200, function ($participants) use ($apply) {
                foreach ($participants as $participant) {
                    if ($participant->user === null) {
                        continue;
                    }

                    if ($apply) {
                        $athlete = $participant->user->athlete
                            ?? app(EnsureAthleteForUser::class)->handle($participant->user, 'backfill');
                        app(LinkParticipantToAthlete::class)->handle($participant, $athlete);
                    }

                    $this->report['participants_linked']++;
                }
            });
    }

    /**
     * Priority 3-5 (§45) — everything else goes through the exact same
     * matcher as import/registration/claim (§46: never a more aggressive
     * pass here). Skips participants that already have a pending conflict
     * from a previous run, so re-running never creates duplicate conflict
     * rows (§48 idempotency) — they stay pending until a human resolves
     * them via /admin/identity-conflicts.
     */
    private function backfillRemainingParticipants(bool $apply): void
    {
        EventParticipant::query()
            ->whereNull('athlete_id')
            ->whereDoesntHave('identityConflicts', fn ($q) => $q->where('status', AthleteIdentityConflictStatus::Pending))
            ->chunkById(200, function ($participants) use ($apply) {
                foreach ($participants as $participant) {
                    $data = AthleteIdentityCandidateData::fromEventParticipant($participant);

                    if ($data->normalizedFullName === '') {
                        $this->report['insufficient_data']++;

                        continue;
                    }

                    if (! $apply) {
                        $outcome = app(AthleteIdentityMatcher::class)->match($data)->outcome;
                        $this->report[match ($outcome->value) {
                            'matched' => 'exact_matches',
                            'conflict' => 'possible_duplicates',
                            default => 'athletes_created',
                        }]++;

                        continue;
                    }

                    $result = app(ResolveAthleteIdentity::class)->handle($data, 'backfill', null, $participant);

                    match ($result->status) {
                        ResolveAthleteIdentityStatus::Matched => $this->report['exact_matches']++,
                        ResolveAthleteIdentityStatus::Created => $this->report['athletes_created']++,
                        ResolveAthleteIdentityStatus::Conflict => $this->report['conflicts']++,
                    };

                    if (in_array($result->status, [ResolveAthleteIdentityStatus::Matched, ResolveAthleteIdentityStatus::Created], true)) {
                        app(LinkParticipantToAthlete::class)->handle($participant, $result->athlete);
                    }
                }
            });
    }

    private function printReport(): void
    {
        $this->newLine();
        $this->table(['Métrica', 'Cantidad'], [
            ['Users procesados (rol athlete)', $this->report['users_processed']],
            ['Athletes creados', $this->report['athletes_created']],
            ['Participants vinculados', $this->report['participants_linked']],
            ['Exact matches', $this->report['exact_matches']],
            ['Conflictos creados', $this->report['conflicts']],
            ['Posibles duplicados (dry-run)', $this->report['possible_duplicates']],
            ['Sin datos suficientes', $this->report['insufficient_data']],
        ]);
    }
}
