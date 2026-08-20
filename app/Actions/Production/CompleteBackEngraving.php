<?php

namespace App\Actions\Production;

use App\Contracts\ProductionActor;
use App\Enums\ProductionJobStatus;
use App\Models\ProductionJob;

class CompleteBackEngraving extends ProductionJobAction
{
    public function handle(ProductionJob $job, ProductionActor $actor): ProductionJob
    {
        $this->assertOwnership($job, $actor);

        return $this->transition(
            $job,
            ProductionJobStatus::VerifyingQr,
            [
                'back_engraved_at' => now(),
                ...ProductionJob::actorAttributes('back', $actor),
            ],
            $actor,
        );
    }
}
