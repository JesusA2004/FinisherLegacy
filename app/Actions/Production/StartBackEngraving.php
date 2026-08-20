<?php

namespace App\Actions\Production;

use App\Contracts\ProductionActor;
use App\Enums\ProductionJobStatus;
use App\Exceptions\Production\FlipNotConfirmedException;
use App\Models\ProductionJob;

class StartBackEngraving extends ProductionJobAction
{
    public function handle(ProductionJob $job, ProductionActor $actor): ProductionJob
    {
        $this->assertOwnership($job, $actor);

        if ($job->flip_confirmed_at === null) {
            throw new FlipNotConfirmedException;
        }

        return $this->transition(
            $job,
            ProductionJobStatus::EngravingBack,
            ['back_started_at' => now()],
            $actor,
        );
    }
}
