<?php

namespace App\Http\Controllers;

use App\Referral;
use Illuminate\Http\Request;

class StaticResourceController extends Controller
{
    public function __construct()
    { }

    // This will just grab the terms from the website
    public function getTerms(Request $request)
    {
        $json = file_get_contents(config('constants.get_terms.' . $request->region));
        if (!$json) {
            return reponse()->json(['error' => 'request.failed'], 500);
        }
        return $json;
    }

    // This will just grab the privacy policy from the website
    public function getPrivacyText(Request $request)
    {
        $json = file_get_contents(config('constants.privacy.' . $request->region));
        if (!$json) {
            return reponse()->json(['error' => 'request.failed'], 500);
        }
        return $json;
    }
}
