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

            // Get current day of the month (1-31)
            $dayOfMonth = (int) now()->format('j');

            // Adjust index (day 1 is index 0)
            $index = ($dayOfMonth - 1) % count($quotes);

            $quote = $quotes[$index];

            return ApiResponse::success($quote);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching daily motivational quote');
        }
    }
}
