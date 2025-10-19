<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function show($userExternalId)
    {
        $user = User::where('external_id', $userExternalId)->firstOrFail();
        $account = Account::where('user_id', $user->id)->firstOrFail();
        return response()->json([
            'user_id' => $user->external_id,
            'currency' => $account->currency,
            'balance' => $account->balance,
        ]);
    }
}
