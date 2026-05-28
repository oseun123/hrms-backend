<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Traits\HandlesApiErrors;

class MotivationalQuoteController extends Controller
{
    use HandlesApiErrors;

    /**
     * Get the motivational quote for the current day.
     */
    public function getDailyQuote(Request $request)
    {
        try {
            $filePath = database_path('data/motivational_quotes.json');

            if (!File::exists($filePath)) {
                return ApiResponse::error('Motivational quotes file not found', 404);
            }

            $quotesJson = File::get($filePath);
            $quotes = json_decode($quotesJson, true);

            if (empty($quotes)) {
                return ApiResponse::error('No motivational quotes found', 404);
            }

            // Pick a random quote instead of day-of-month to increase variety
            $quote = $quotes[array_rand($quotes)];

            return ApiResponse::success($quote);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching daily motivational quote');
        }
    }
}
