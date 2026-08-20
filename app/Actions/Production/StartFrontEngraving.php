<?php

namespace App\Actions\Production;

use App\Contracts\ProductionActor;
use App\Enums\ProductionJobStatus;
use App\Exceptions\Production\ArtifactNotReadyException;
use App\Models\ProductionJob;

class StartFrontEngraving extends ProductionJobAction
{
    public function handle(ProductionJob $job, ProductionActor $actor): ProductionJob
    {
        $this->assertOwnership($job, $actor);

        if ($job->artifact()->doesntExist()) {
            throw new ArtifactNotReadyException;
        }

        return $this->transition(
            $job,
            ProductionJobStatus::EngravingFront,
            ['front_started_at' => now()],
            $actor,
        );
    }
}
