<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventOps\ParticipantDetailResource;
use App\Http\Resources\Api\V1\EventOps\ParticipantSummaryResource;
use App\Http\Resources\Api\V1\EventOps\PlateResource;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Queries\Operations\GetEventOperationsDashboard;
use App\Queries\Operations\GetParticipantOperationsDetail;
use App\Queries\Operations\SearchEventParticipants;
use App\Services\PlateGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The API mirror of App\Http\Controllers\OperatorController — same
 * Queries/Actions, different transport (docs/api/use-case-matrix.md).
 * Nothing here re-derives eligibility, search priority, or plate
 * generation rules; every method is a thin translate-to-JSON layer.
 */
class EventOpsController extends Controller
{
    use ApiResponses;

    public function dashboard(EventEdition $eventEdition, GetEventOperationsDashboard $dashboard): JsonResponse
    {
        return $this->respond($dashboard->handle($eventEdition));
    }

    public function searchParticipants(EventEdition $eventEdition, Request $request, SearchEventParticipants $search): JsonResponse
    {
        $participants = $search->handle($eventEdition, (string) $request->query('q', ''));

        return $this->respond(ParticipantSummaryResource::collection($participants));
    }

    public function participant(EventParticipant $eventParticipant, GetParticipantOperationsDetail $detail): JsonResponse
    {
        return $this->respond(new ParticipantDetailResource($detail->handle($eventParticipant)));
    }

    /**
     * Same idempotency guard as the Web operator console — a Plate can
     * never be produced twice for one participant — but here it's
     * App\Services\PlateGenerationService itself throwing
     * App\Exceptions\PlateAlreadyExistsException, caught nowhere in this
     * controller: it's an App\Exceptions\Api\ApiException, so
     * App\Support\Api\ApiExceptionRenderer renders it automatically as a
     * 409. Send an `Idempotency-Key` header (routes/api.php's
     * `api.idempotent` middleware) to make a retried request replay
     * instead of hitting that 409.
     */
    public function generatePlate(EventParticipant $eventParticipant, PlateGenerationService $plates): JsonResponse
    {
        $plate = $plates->generateIntegrated($eventParticipant);

        return $this->respond(new PlateResource($plate), 'Placa generada y enviada a producción.', status: 201);
    }
}
