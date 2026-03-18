<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Queue\Attributes\WithoutRelations;

#[Tries(3)]
#[Timeout(120)]
#[Backoff(10)]
#[UniqueFor(3600)]
#[FailOnTimeout]
#[WithoutRelations]
class ProcessOrder implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order) {}

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): int
    {
        return $this->order->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->order->update(['status' => OrderStatus::Processing]);

        // Simulate order processing
        sleep(1);

        $this->order->update(['status' => OrderStatus::Completed]);
    }
}
