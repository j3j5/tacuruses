<?php

namespace App\Http\Controllers\API\Mastodon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LookupAccount extends Controller
{
    public function __invoke(Request $request)
    {
        // TODO: see https://example.com/api/v1/accounts/lookup?acct=account-username
        abort(501, 'Not implemented yet');
    }
}
