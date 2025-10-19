<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Alert;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class FraudDetector
{
    public static function evaluate(Account $account, Transaction $transaction)
    {
        $threshold = 10000;
        $windowMinutes = 1;
        $count = Transaction::where('account_id', $account->id)
            ->where('occurred_at', '>=', now()->subMinutes($windowMinutes))
            ->where('amount', '>=', $threshold)
            ->where('status', 'processed')
            ->count();

        if ($transaction->amount >= $threshold && $count >= 3) {
            Alert::create([
                'account_id' => $account->id,
                'rule' => 'high_value_burst',
                'details' => json_encode(['count' => $count, 'threshold' => $threshold]),
            ]);
            Log::warning('Fraud alert: high_value_burst', ['account_id'=>$account->id,'count'=>$count]);
        }
    }

}
