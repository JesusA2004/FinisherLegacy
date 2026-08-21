<?php

namespace App\Support\Devices;

use App\Enums\DeviceErrorCode;
use App\Exceptions\Devices\DeviceApiException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Every error under the Device API surface (`/api/v1/devices*`,
 * `/api/v1/device*`, `/api/v1/production/*`) renders as
 * `{"error": {"code", "message", "details"}}` — see docs/device-api/v1.md
 * §Errores. This is the one place that mapping happens, so no device
 * controller needs its own try/catch-to-JSON boilerplate. Registered from
 * bootstrap/app.php, scoped by request path so the rest of the app (web
 * Inertia, `/api/v1/*` non-device) keeps its existing error shape.
 */
class DeviceExceptionRenderer
{
    public function render(Throwable $e): JsonResponse
    {
        if ($e instanceof DeviceApiException) {
            return $this->respond($e->deviceErrorCode(), $e->getMessage(), $e->details(), $e->status());
        }

        if ($e instanceof ValidationException) {
            return $this->respond(DeviceErrorCode::ValidationFailed, 'Los datos enviados no son válidos.', $e->errors(), 422);
        }

        if ($e instanceof AuthenticationException) {
            return $this->respond(DeviceErrorCode::Unauthenticated, 'Token de dispositivo ausente o inválido.', [], 401);
        }

        if ($e instanceof AuthorizationException) {
            return $this->respond(DeviceErrorCode::Forbidden, 'El dispositivo no tiene permiso para esta acción.', [], 403);
        }

        if ($e instanceof ModelNotFoundException) {
            return $this->respond(DeviceErrorCode::NotFound, 'Recurso no encontrado.', [], 404);
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            return $this->respond(
                $status === 404 ? DeviceErrorCode::NotFound : DeviceErrorCode::HttpError,
                $e->getMessage() !== '' ? $e->getMessage() : 'Error de solicitud.',
                [],
                $status,
            );
        }

        report($e);

        return $this->respond(DeviceErrorCode::InternalError, 'Error interno del servidor.', [], 500);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function respond(DeviceErrorCode $code, string $message, array $details, int $status): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code->value,
                'message' => $message,
                'details' => (object) $details,
            ],
            'request_id' => request()->attributes->get('request_id'),
        ], $status);
    }
}
