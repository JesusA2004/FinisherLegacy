<?php

namespace Database\Seeders;

use App\Enums\LegacyCodeStatus;
use App\Enums\PlateGenerationMode;
use App\Enums\PlateStatus;
use App\Enums\ProductionJobStatus;
use App\Enums\StaffRole;
use App\Enums\UserStatus;
use App\Models\AthleteProfile;
use App\Models\Event;
use App\Models\EventEdition;
use App\Models\EventIncident;
use App\Models\EventParticipant;
use App\Models\EventRace;
use App\Models\EventStaffAssignment;
use App\Models\LegacyCode;
use App\Models\Medal;
use App\Models\Organizer;
use App\Models\Plate;
use App\Models\PlateTemplate;
use App\Models\ProductionJob;
use App\Models\Sport;
use App\Models\User;
use App\Services\LegacyIdService;
use App\Support\CodeGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private LegacyIdService $legacyIdService;

    public function run(): void
    {
        $this->legacyIdService = app(LegacyIdService::class);

        $staff = $this->createStaffUsers();
        $athlete = $this->createDemoAthlete();

        $template = PlateTemplate::query()->where('slug', 'classic-black-gold')->first();

        [$edition, $races] = $this->createDemoEvent($staff);

        $this->assignStaff($edition, $staff);

        $participants = $this->createParticipants($edition, $races, 500);
        $this->linkAthleteToParticipant($participants->first(), $athlete);
        $this->createResults($participants);

        $integratedParticipant = $participants->first();
        $this->createIntegratedPlate($integratedParticipant, $athlete, $edition, $template);
        $this->createQuickPlates($edition, $template, 6);
        $this->createPreregistrations($edition, $races, 30);
        $this->createSampleIncident($edition, $participants->skip(1)->first(), $staff['event_operator']);
        $this->createDemoMedals($athlete);
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

    private function createIntegratedPlate(EventParticipant $participant, User $athlete, EventEdition $edition, ?PlateTemplate $template): void
    {
        $result = $participant->result;

        $plate = Plate::create([
            'user_id' => $athlete->id,
            'event_edition_id' => $edition->id,
            'event_participant_id' => $participant->id,
            'plate_template_id' => $template?->id,
            'serial_number' => CodeGenerator::generate('PLT', 8),
            'generation_mode' => PlateGenerationMode::Integrated,
            'athlete_name' => $athlete->name,
            'bib_number' => $participant->bib_number,
            'event_name' => 'Maratón CDMX 2026',
            'race_name' => $participant->eventRace->name,
            'official_time' => $result?->official_time,
            'pace' => $result?->pace,
            'event_date' => $edition->event_date,
            'status' => PlateStatus::Delivered,
            'linked_at' => now(),
            'produced_at' => now()->subHour(),
            'delivered_at' => now(),
        ]);

        $legacyCode = LegacyCode::create([
            'code' => CodeGenerator::unique('FL', fn (string $c) => LegacyCode::query()->where('code', $c)->exists()),
            'uuid' => Str::uuid(),
            'plate_id' => $plate->id,
            'user_id' => $athlete->id,
            'status' => LegacyCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);

        $plate->update(['legacy_code_id' => $legacyCode->id]);

        ProductionJob::create([
            'plate_id' => $plate->id,
            'event_edition_id' => $edition->id,
            'priority' => 0,
            'status' => ProductionJobStatus::Completed,
            'queued_at' => now()->subHours(2),
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'attempts' => 1,
        ]);
    }

    private function createQuickPlates(EventEdition $edition, ?PlateTemplate $template, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $claimed = $i <= 2;

            $plate = Plate::create([
                'user_id' => null,
                'event_edition_id' => $edition->id,
                'plate_template_id' => $template?->id,
                'serial_number' => CodeGenerator::generate('PLT', 8),
                'generation_mode' => PlateGenerationMode::Quick,
                'athlete_name' => fake()->name(),
                'bib_number' => null,
                'event_name' => 'Maratón CDMX 2026',
                'race_name' => fake()->randomElement(['5K', '10K', '21K', '42K']),
                'event_date' => $edition->event_date,
                'status' => PlateStatus::Delivered,
                'produced_at' => now()->subHours(3),
                'delivered_at' => now()->subHours(2),
            ]);

            $legacyCode = LegacyCode::create([
                'code' => CodeGenerator::unique('FL', fn (string $c) => LegacyCode::query()->where('code', $c)->exists()),
                'uuid' => Str::uuid(),
                'plate_id' => $plate->id,
                'status' => LegacyCodeStatus::Assigned,
                'assigned_at' => now()->subHours(3),
            ]);

            $plate->update(['legacy_code_id' => $legacyCode->id]);

            if ($claimed) {
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

            ProductionJob::create([
                'plate_id' => $plate->id,
                'event_edition_id' => $edition->id,
                'status' => ProductionJobStatus::Completed,
                'queued_at' => now()->subHours(4),
                'started_at' => now()->subHours(3.5),
                'completed_at' => now()->subHours(3),
                'attempts' => 1,
            ]);
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
