<?php

namespace App\Enums;

/**
 * Machine-readable codes for the Device API's `{"error": {"code", ...}}`
 * contract (docs/device-api/v1.md) — a desktop client branches on `code`,
 * never on the human `message` text, which is allowed to change.
 */
enum DeviceErrorCode: string
{
    case ValidationFailed = 'VALIDATION_FAILED';
    case Unauthenticated = 'UNAUTHENTICATED';
    case Forbidden = 'FORBIDDEN';
    case NotFound = 'NOT_FOUND';
    case HttpError = 'HTTP_ERROR';
    case InternalError = 'INTERNAL_ERROR';

    case PairingRequestNotFound = 'PAIRING_REQUEST_NOT_FOUND';
    case PairingExpired = 'PAIRING_EXPIRED';
    case PairingNotApproved = 'PAIRING_NOT_APPROVED';
    case PairingAlreadyCompleted = 'PAIRING_ALREADY_COMPLETED';

    case DeviceRevoked = 'DEVICE_REVOKED';

    case NoProductionJobAvailable = 'NO_PRODUCTION_JOB_AVAILABLE';
    case ProductionJobNotFound = 'PRODUCTION_JOB_NOT_FOUND';
    case ProductionJobAlreadyClaimed = 'PRODUCTION_JOB_ALREADY_CLAIMED';
    case ProductionJobForbidden = 'PRODUCTION_JOB_FORBIDDEN';

    // Slice 2 — production state machine (docs/adr/0003)
    case InvalidProductionTransition = 'INVALID_PRODUCTION_TRANSITION';
    case JobNotAssigned = 'JOB_NOT_ASSIGNED';
    case JobOwnedByOtherDevice = 'JOB_OWNED_BY_OTHER_DEVICE';
    case ArtifactNotReady = 'ARTIFACT_NOT_READY';
    case FrontNotCompleted = 'FRONT_NOT_COMPLETED';
    case FlipNotConfirmed = 'FLIP_NOT_CONFIRMED';
    case BackNotCompleted = 'BACK_NOT_COMPLETED';
    case QrVerificationFailed = 'QR_VERIFICATION_FAILED';
    case JobAlreadyCompleted = 'JOB_ALREADY_COMPLETED';
    case JobFailed = 'JOB_FAILED';
    case DeviceLeaseExpired = 'DEVICE_LEASE_EXPIRED';
}
