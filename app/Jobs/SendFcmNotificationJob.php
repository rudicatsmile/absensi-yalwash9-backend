<?php

namespace App\Jobs;

use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     */
    public function __construct(
        protected array $tokens,
        protected string $title,
        protected string $body,
        protected array $data = []
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(FcmService $fcmService)
    {
        Log::info('SendFcmNotificationJob: Executing job...', [
            'title' => $this->title,
            'tokens_count' => count($this->tokens)
        ]);

        $fcmService->sendToTokens(
            $this->tokens,
            $this->title,
            $this->body,
            $this->data
        );
    }
}
