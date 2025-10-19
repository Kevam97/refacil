<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\transaction;
use App\Services\FraudDetector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public transaction $transaction) {}

    /**
     * Execute the job.
     */
    public function handle()
    {
        DB::transaction(function () {
            $transaction = Transaction::where('id', $this->transaction->id)->lockForUpdate()->first();

            if ($transaction->status !== 'pending') {
                return;
            }

            $account = Account::where('id', $transaction->account_id)->lockForUpdate()->first();

            try {
                if ($transaction->type === 'withdraw') {
                    if ($account->balance < $transaction->amount) {
                        $transaction->status = 'failed';
                        $transaction->reason = 'insufficient_funds';
                        $transaction->save();
                        Log::warning('Withdraw failed: insufficient funds', ['transaction_id'=>$transaction->transaction_id]);
                        return;
                    }
                    $account->balance = bcsub($account->balance, $transaction->amount, 6);
                } else {
                    $account->balance = bcadd($account->balance, $transaction->amount, 6);
                }

                $account->save();

                $transaction->status = 'processed';
                $transaction->save();

                Log::info('Transaction processed', ['transaction_id'=>$transaction->transaction_id,'account_id'=>$account->id,'type'=>$transaction->type,'amount'=>$transaction->amount]);

                FraudDetector::evaluate($account, $transaction);

            } catch (\Throwable $e) {
                $transaction->status = 'failed';
                $transaction->reason = $e->getMessage();
                $transaction->save();
                Log::error('Transaction processing error', ['transaction_id'=>$transaction->transaction_id,'error'=>$e->getMessage()]);
                throw $e;
            }
        }, 5);
    }
}
