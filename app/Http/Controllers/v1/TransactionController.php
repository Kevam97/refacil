<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Jobs\ProcessTransactionJob;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function store(TransactionRequest $request)
    {
        $data = $request->validated();
        $user = User::firstOrCreate(['external_id'=>$data['user_id'],
            'email'=>$data['email']]);

        $account = Account::firstOrCreate(
            ['user_id' => $user->id, 'currency' => $data['currency'] ?? 'USD'],
            ['balance'=>0]
        );

        $transaction = Transaction::create([
            'transaction_id' => $data['transaction_id'],
            'account_id' => $account->id,
            'amount' => $data['amount'],
            'type' => $data['type'],
            'occurred_at' => now(),
            'metadata' =>  json_encode($request->except([
                'transaction_id','user_id','amount','type','timestamp'
            ])),
            'status' => 'pending',
        ]);

        ProcessTransactionJob::dispatch($transaction)->onQueue('transactions');

        return response()->json([
            'status' => 'accepted',
            'transaction_id' => $transaction->transaction_id,
        ], 202);
    }

    public function history($userExternalId)
    {
        $user = User::where('external_id', $userExternalId)->firstOrFail();

        $transactions = Transaction::whereHas('account', function($q) use($user){
            $q->where('user_id', $user->id);
        })->orderBy('occurred_at','desc')->paginate(50);

        return response()->json($transactions);
    }

}
