<?php

namespace App\Actions\Production;

use App\Contracts\ProductionActor;
use App\Enums\ProductionJobStatus;
use App\Models\ProductionJob;
use Illuminate\Database\Eloquent\Model;

class DeliverProductionPlate extends ProductionJobAction
{
    public function handle(ProductionJob $job, ProductionActor $actor): ProductionJob
    {
        $this->assertOwnership($job, $actor);

        $job = $this->transition($job, ProductionJobStatus::Delivered, ['delivered_at' => now()], $actor);
        $job->loadMissing('plate');

        activity()
            ->causedBy($actor instanceof Model ? $actor : null)
            ->performedOn($job)
            ->log(ucfirst($actor->productionActorLabel())." marcó la placa {$job->plate->serial_number} como entregada.");

        return $job;
    }
}
