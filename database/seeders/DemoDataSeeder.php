<?php

namespace Database\Seeders;

use App\Enums\LegacyCodeStatus;
use App\Enums\PlateStatus;
use App\Enums\PlateTemplateVersionStatus;
use App\Enums\ProductionJobStatus;
use App\Enums\StaffRole;
use App\Enums\UserStatus;
use App\Models\AthleteProfile;
use App\Models\Event;
use App\Models\EventEdition;
use App\Models\EventIncident;
use App\Models\EventParticipant;
use App\Models\EventPlateTemplate;
use App\Models\EventRace;
use App\Models\EventResult;
use App\Models\EventStaffAssignment;
use App\Models\Medal;
use App\Models\Organizer;
use App\Models\Plate;
use App\Models\PlateTemplateVersion;
use App\Models\Sport;
use App\Models\User;
use App\Services\LegacyIdService;
use App\Services\PlateGenerationService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Every demo Plate — integrated, quick, triathlon — is created through
 * PlateGenerationService, the same single entry point the real app uses
 * (see App\Services\PlateGenerationService docblock). This seeder never
 * builds a Plate row by hand; it only mutates status/timestamps
 * afterward to make old demo data look historical (delivered days ago,
 * claimed, etc.) — that's just simulating time passing, not a second way
 * to create a plate.
 */
class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private LegacyIdService $legacyIdService;

    private PlateGenerationService $plates;

    public function run(): void
    {
        $this->legacyIdService = app(LegacyIdService::class);
        $this->plates = app(PlateGenerationService::class);

        $staff = $this->createStaffUsers();
        $athlete = $this->createDemoAthlete();

        // PlateTemplateSeeder (runs before this one in DatabaseSeeder) is the
        // only place a template + published version actually gets created.
        // Every demo plate must carry a real plate_template_version_id or
        // export/production tooling breaks on a NULL template downstream.
        $runningVersion = $this->publishedVersion('running-classic-60x40');

        [$edition, $races] = $this->createDemoEvent($staff);

        $this->assignDefaultTemplate($edition, $runningVersion);
        $this->assignStaff($edition, $staff);

        $participants = $this->createParticipants($edition, $races, 500);
        $marathonRace = $races->firstWhere('name', '42K') ?? $races->first();
        $participants->first()->update(['event_race_id' => $marathonRace->id]);
        $this->linkAthleteToParticipant($participants->first(), $athlete);
        $this->createResults($participants);

        $integratedParticipant = $participants->first()->fresh(['eventRace', 'result']);
        $this->finalizeMarathonResult($integratedParticipant);
        $integratedParticipant->refresh();
        $this->createIntegratedPlate($integratedParticipant, $athlete, $edition, $runningVersion);
        $this->createQuickPlates($edition, $runningVersion, 6);
        $this->createPreregistrations($edition, $races, 30);
        $this->createSampleIncident($edition, $participants->skip(1)->first(), $staff['event_operator']);
        $this->createDemoMedals($athlete);

        $this->createTriathlonDemoEvent($staff);
    }

    private function publishedVersion(string $slug): PlateTemplateVersion
    {
        return PlateTemplateVersion::query()
            ->whereHas('plateTemplate', fn ($q) => $q->where('slug', $slug))
            ->where('status', PlateTemplateVersionStatus::Published)
            ->latest('version')
            ->firstOrFail();
    }

    private function createDemoMedals(User $athlete): void
    {
        foreach ([
            ['title' => 'Maratón CDMX 2026', 'distance_label' => '42K', 'official_time' => '03:47:21', 'city' => 'Ciudad de México'],
            ['title' => 'Carrera Nocturna Guadalajara', 'distance_label' => '10K', 'official_time' => '00:48:03', 'city' => 'Guadalajara'],
            ['title' => 'Medio Maratón Querétaro', 'distance_label' => '21K', 'official_time' => '01:52:11', 'city' => 'Querétaro'],
        ] as $medal) {
            Medal::query()->firstOrCreate(
                ['user_id' => $athlete->id, 'title' => $medal['title']],
                [
                    'event_name_manual' => $medal['title'],
                    'event_date' => now()->subMonths(random_int(1, 18)),
                    'distance_label' => $medal['distance_label'],
                    'official_time' => $medal['official_time'],
                    'city' => $medal['city'],
                    'country' => 'México',
                    'visibility' => 'public',
                    'status' => 'active',
                ],
            );
        }
    }

    /**
     * @return array<string, User>
     */
    private function createStaffUsers(): array
    {
        $definitions = [
            'super_admin' => ['Finisher', 'Root', 'super@finisherlegacy.com'],
            'admin' => ['Ana', 'Administradora', 'admin@finisherlegacy.com'],
            'event_manager' => ['Marco', 'Gestor', 'manager@finisherlegacy.com'],
            'event_operator' => ['Laura', 'Operadora', 'operator@finisherlegacy.com'],
            'production_operator' => ['Diego', 'Producción', 'production@finisherlegacy.com'],
        ];

        $users = [];

        foreach ($definitions as $role => [$firstName, $lastName, $email]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => 'password',
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$role]);
            $users[$role] = $user;
        }

        return $users;
    }

    private function createDemoAthlete(): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'athlete@finisherlegacy.com'],
            [
                'first_name' => 'Zuriel',
                'last_name' => 'Ávila',
                'password' => 'password',
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles(['athlete']);

        $this->legacyIdService->issueFor($user);

        AthleteProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'username' => 'zurielavila',
                'bio' => 'Corredor de fondo. Finisher Legacy demo profile.',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'country' => 'México',
                'main_sport_id' => Sport::query()->where('slug', 'running')->value('id'),
                'profile_visibility' => 'public',
            ],
        );

        return $user;
    }

    /**
     * @param  array<string, User>  $staff
     * @return array{0: EventEdition, 1: Collection<int, EventRace>}
     */
    private function createDemoEvent(array $staff): array
    {
        $organizer = Organizer::factory()->create([
            'name' => 'Finisher Sports Group',
            'slug' => 'finisher-sports-group',
        ]);

        $sport = Sport::query()->where('slug', 'running')->firstOrFail();

        $event = Event::query()->updateOrCreate(
            ['slug' => 'maraton-ciudad-de-mexico'],
            [
                'organizer_id' => $organizer->id,
                'sport_id' => $sport->id,
                'name' => 'Maratón de la Ciudad de México',
                'description' => 'El maratón más importante de México, recorriendo los puntos más emblemáticos de la capital.',
                'status' => 'published',
            ],
        );

        $edition = EventEdition::query()->updateOrCreate(
            ['event_id' => $event->id, 'year' => 2026],
            [
                'name' => 'Maratón CDMX 2026',
                'event_date' => now()->addMonths(2)->toDateString(),
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'country' => 'México',
                'timezone' => 'America/Mexico_City',
                'registration_open_at' => now()->subMonths(3),
                'registration_close_at' => now()->subDays(10),
                'operation_mode' => 'hybrid',
                'status' => 'published',
                'results_status' => 'partial',
            ],
        );

        $races = collect([
            ['name' => '5K', 'distance_value' => 5, 'distance_unit' => 'km'],
            ['name' => '10K', 'distance_value' => 10, 'distance_unit' => 'km'],
            ['name' => '21K', 'distance_value' => 21.097, 'distance_unit' => 'km'],
            ['name' => '42K', 'distance_value' => 42.195, 'distance_unit' => 'km'],
        ])->map(fn (array $race) => EventRace::query()->updateOrCreate(
            ['event_edition_id' => $edition->id, 'name' => $race['name']],
            [
                'distance_value' => $race['distance_value'],
                'distance_unit' => $race['distance_unit'],
                'race_type' => 'individual',
                'start_time' => '06:00:00',
                'active' => true,
            ],
        ));

        return [$edition, $races];
    }

    private function assignDefaultTemplate(EventEdition $edition, PlateTemplateVersion $version): void
    {
        EventPlateTemplate::query()->updateOrCreate(
            ['event_edition_id' => $edition->id, 'event_race_id' => null],
            [
                'plate_template_version_id' => $version->id,
                'is_default' => true,
                'active' => true,
            ],
        );
    }

    /**
     * @param  array<string, User>  $staff
     */
    private function assignStaff(EventEdition $edition, array $staff): void
    {
        foreach ([
            'event_manager' => StaffRole::EventManager,
            'event_operator' => StaffRole::EventOperator,
            'production_operator' => StaffRole::ProductionOperator,
        ] as $key => $role) {
            EventStaffAssignment::query()->firstOrCreate([
                'event_edition_id' => $edition->id,
                'user_id' => $staff[$key]->id,
                'role' => $role,
            ]);
        }
    }

    /**
     * @param  Collection<int, EventRace>  $races
     * @return Collection<int, EventParticipant>
     */
    private function createParticipants(EventEdition $edition, $races, int $count)
    {
        $rows = [];
        $now = now();

        for ($i = 1; $i <= $count; $i++) {
            $race = $races->random();
            $firstName = fake()->firstName();
            $lastName = fake()->lastName();

            $rows[] = [
                'event_edition_id' => $edition->id,
                'event_race_id' => $race->id,
                'user_id' => null,
                'external_participant_id' => null,
                'bib_number' => (string) (1000 + $i),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => "{$firstName} {$lastName}",
                'email' => fake()->optional()->safeEmail(),
                'phone' => null,
                'gender' => fake()->randomElement(['M', 'F']),
                'birth_date' => fake()->dateTimeBetween('-60 years', '-16 years')->format('Y-m-d'),
                'category' => fake()->randomElement(['18-29', '30-39', '40-49', '50-59', '60+']),
                'registration_status' => 'registered',
                'source' => 'csv',
                'source_reference' => 'seed-import.csv',
                'verification_status' => 'unverified',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('event_participants')->insert($rows);

        return EventParticipant::query()->where('event_edition_id', $edition->id)->orderBy('id')->get();
    }

    private function linkAthleteToParticipant(EventParticipant $participant, User $athlete): void
    {
        $participant->update([
            'user_id' => $athlete->id,
            'first_name' => $athlete->first_name,
            'last_name' => $athlete->last_name,
            'full_name' => $athlete->name,
            'email' => $athlete->email,
            'verification_status' => 'verified',
        ]);
    }

    /**
     * @param  Collection<int, EventParticipant>  $participants
     */
    private function createResults($participants): void
    {
        $withResults = $participants->take((int) ($participants->count() * 0.9));
        $rows = [];
        $now = now();

        foreach ($withResults as $participant) {
            $seconds = fake()->numberBetween(20 * 60, 6 * 3600);
            $time = gmdate($seconds >= 3600 ? 'H:i:s' : 'i:s', $seconds);
            $paceSeconds = fake()->numberBetween(240, 420);

            $rows[] = [
                'event_participant_id' => $participant->id,
                'official_time' => $time,
                'chip_time' => $time,
                'pace' => sprintf('%d:%02d', intdiv($paceSeconds, 60), $paceSeconds % 60),
                'overall_position' => fake()->numberBetween(1, $participants->count()),
                'gender_position' => fake()->numberBetween(1, 2500),
                'category_position' => fake()->numberBetween(1, 500),
                'status' => 'finished',
                'result_source' => 'timing_import',
                'verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('event_results')->insert($rows);
    }

    /**
     * Gives the linked demo athlete's marathon result clean, demo-friendly
     * numbers (rather than the random ones createResults() gave everyone
     * else) and 5K/10K/21K/30K splits — coherent enough to prove the UI and
     * the Integrated → Plate → SVG pipeline, not statistically real.
     */
    private function finalizeMarathonResult(EventParticipant $participant): void
    {
        $result = $participant->result ?? EventResult::create([
            'event_participant_id' => $participant->id,
            'status' => 'finished',
        ]);

        $result->update([
            'official_time' => '03:47:21',
            'chip_time' => '03:47:18',
            'pace' => '5:23',
            'overall_position' => 142,
            'gender_position' => 98,
            'category_position' => 18,
            'status' => 'finished',
            'result_source' => 'timing_import',
            'verified_at' => now(),
        ]);

        $splits = [
            ['type' => 'split', 'label' => '5K', 'sequence' => 1, 'distance_value' => 5, 'distance_unit' => 'km', 'segment_time' => '00:25:40', 'elapsed_time' => '00:25:40', 'pace' => '5:08'],
            ['type' => 'split', 'label' => '10K', 'sequence' => 2, 'distance_value' => 10, 'distance_unit' => 'km', 'segment_time' => '00:26:55', 'elapsed_time' => '00:52:35', 'pace' => '5:23'],
            ['type' => 'split', 'label' => '21K', 'sequence' => 3, 'distance_value' => 21.097, 'distance_unit' => 'km', 'segment_time' => '00:58:10', 'elapsed_time' => '01:50:45', 'pace' => '5:14'],
            ['type' => 'split', 'label' => '30K', 'sequence' => 4, 'distance_value' => 30, 'distance_unit' => 'km', 'segment_time' => '00:49:32', 'elapsed_time' => '02:40:17', 'pace' => '5:31'],
        ];

        foreach ($splits as $split) {
            $result->splits()->updateOrCreate(['label' => $split['label']], $split);
        }
    }

    /**
     * A second, dedicated demo event for the Triathlon preset — the running
     * marathon above never exercises swim/bike/run at all. Generic name,
     * no protected/real event branding.
     *
     * @param  array<string, User>  $staff
     */
    private function createTriathlonDemoEvent(array $staff): void
    {
        $version = $this->publishedVersion('triathlon-premium-60x40');

        $organizer = Organizer::query()->firstOrCreate(
            ['slug' => 'finisher-sports-group'],
            ['name' => 'Finisher Sports Group'],
        );
        $sport = Sport::query()->where('slug', 'triatlon')->first()
            ?? Sport::query()->where('slug', 'running')->firstOrFail();

        $event = Event::query()->updateOrCreate(
            ['slug' => 'finisher-legacy-triathlon-demo'],
            [
                'organizer_id' => $organizer->id,
                'sport_id' => $sport->id,
                'name' => 'Finisher Legacy Triathlon Demo',
                'description' => 'Evento de demostración para validar el preset de Triatlón (swim/bike/run) — no es un evento real.',
                'status' => 'published',
            ],
        );

        $edition = EventEdition::query()->updateOrCreate(
            ['event_id' => $event->id, 'year' => 2026],
            [
                'name' => 'Finisher Legacy Triathlon Demo 2026',
                'event_date' => now()->addMonths(3)->toDateString(),
                'city' => 'Cozumel',
                'state' => 'Quintana Roo',
                'country' => 'México',
                'timezone' => 'America/Cancun',
                'registration_open_at' => now()->subMonth(),
                'registration_close_at' => now()->addWeeks(6),
                'operation_mode' => 'hybrid',
                'status' => 'published',
                'results_status' => 'partial',
            ],
        );

        $race = EventRace::query()->updateOrCreate(
            ['event_edition_id' => $edition->id, 'name' => '70.3'],
            [
                'distance_value' => 70.3,
                'distance_unit' => 'mi',
                'race_type' => 'individual',
                'start_time' => '07:00:00',
                'active' => true,
            ],
        );

        $this->assignDefaultTemplate($edition, $version);
        $this->assignStaff($edition, $staff);

        $athlete = User::query()->where('email', 'athlete@finisherlegacy.com')->first();

        $participant = EventParticipant::query()->updateOrCreate(
            ['event_edition_id' => $edition->id, 'bib_number' => '142'],
            [
                'event_race_id' => $race->id,
                'user_id' => $athlete?->id,
                'first_name' => 'Zuriel',
                'last_name' => 'Ávila',
                'full_name' => 'Zuriel Ávila',
                'email' => 'athlete@finisherlegacy.com',
                'registration_status' => 'registered',
                'source' => 'csv',
                'source_reference' => 'seed-import.csv',
                'verification_status' => 'verified',
                'category' => '25-29',
            ],
        );

        $result = EventResult::query()->updateOrCreate(
            ['event_participant_id' => $participant->id],
            [
                'official_time' => '05:21:18',
                'chip_time' => '05:21:15',
                'pace' => '4:33/km',
                'overall_position' => 142,
                'gender_position' => 89,
                'category_position' => 18,
                'status' => 'finished',
                'result_source' => 'timing_import',
                'verified_at' => now(),
            ],
        );

        foreach ([
            ['type' => 'swim', 'label' => 'Swim', 'sequence' => 1, 'distance_value' => 1.9, 'distance_unit' => 'km', 'segment_time' => '42:15', 'elapsed_time' => '42:15'],
            ['type' => 'bike', 'label' => 'Bike', 'sequence' => 2, 'distance_value' => 90, 'distance_unit' => 'km', 'segment_time' => '2:49:33', 'elapsed_time' => '3:31:48'],
            ['type' => 'run', 'label' => 'Run', 'sequence' => 3, 'distance_value' => 21.1, 'distance_unit' => 'km', 'segment_time' => '1:44:51', 'elapsed_time' => '5:21:18'],
        ] as $split) {
            $result->splits()->updateOrCreate(['type' => $split['type']], $split);
        }

        if ($athlete && ! Plate::where('event_participant_id', $participant->id)->exists()) {
            $plate = $this->plates->generateIntegrated($participant->fresh(['eventRace', 'result.splits']), $version);
            $plate->update([
                'status' => PlateStatus::Delivered,
                'produced_at' => now()->subDays(2),
                'delivered_at' => now()->subDay(),
            ]);
            $plate->latestProductionJob?->update([
                'status' => ProductionJobStatus::Delivered,
                'queued_at' => now()->subDays(2),
                'started_at' => now()->subDays(2),
                'completed_at' => now()->subDays(2),
                'attempts' => 1,
            ]);
        }
    }

    private function createIntegratedPlate(EventParticipant $participant, User $athlete, EventEdition $edition, PlateTemplateVersion $version): void
    {
        $plate = $this->plates->generateIntegrated($participant, $version);

        // Simulating time passing on an already-real plate — not a second
        // way to create one. The service already froze the correct
        // dynamic_fields snapshot (distance, splits, position, category).
        $plate->update([
            'status' => PlateStatus::Delivered,
            'produced_at' => now()->subHour(),
            'delivered_at' => now(),
        ]);

        $plate->latestProductionJob?->update([
            'status' => ProductionJobStatus::Delivered,
            'queued_at' => now()->subHours(2),
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'attempts' => 1,
        ]);
    }

    private function createQuickPlates(EventEdition $edition, PlateTemplateVersion $version, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $claimed = $i <= 2;

            $plate = $this->plates->generateQuick($edition, [
                'athlete_name' => fake()->name(),
                'race_name' => fake()->randomElement(['5K', '10K', '21K', '42K']),
            ], $version);

            $plate->update([
                'status' => PlateStatus::Delivered,
                'produced_at' => now()->subHours(3),
                'delivered_at' => now()->subHours(2),
            ]);

            $plate->latestProductionJob?->update([
                'status' => ProductionJobStatus::Delivered,
                'queued_at' => now()->subHours(4),
                'started_at' => now()->subHours(3.5),
                'completed_at' => now()->subHours(3),
                'attempts' => 1,
            ]);

            $legacyCode = $plate->legacyCode;

            if ($claimed && $legacyCode) {
                $claimant = User::factory()->create();
                $claimant->syncRoles(['athlete']);
                $this->legacyIdService->issueFor($claimant);

                $legacyCode->update([
                    'status' => LegacyCodeStatus::Claimed,
                    'user_id' => $claimant->id,
                    'claimed_by_user_id' => $claimant->id,
                    'claimed_at' => now(),
                ]);

                $plate->update(['user_id' => $claimant->id, 'linked_at' => now()]);
            }
        }
    }

    /**
     * @param  Collection<int, EventRace>  $races
     */
    private function createPreregistrations(EventEdition $edition, $races, int $count): void
    {
        $rows = [];
        $now = now();

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'event_edition_id' => $edition->id,
                'event_race_id' => $races->random()->id,
                'user_id' => null,
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => null,
                'bib_number' => null,
                'token' => Str::random(32),
                'qr_token' => Str::random(24),
                'status' => 'pending',
                'matched_participant_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('event_preregistrations')->insert($rows);
    }

    private function createSampleIncident(EventEdition $edition, EventParticipant $participant, User $reporter): void
    {
        EventIncident::create([
            'event_edition_id' => $edition->id,
            'event_participant_id' => $participant->id,
            'reported_by' => $reporter->id,
            'type' => 'incorrect_name',
            'description' => 'El nombre en el bib no coincide con la identificación presentada.',
            'status' => 'open',
        ]);
    }
}
