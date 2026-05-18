<?php

namespace App\Http\Controllers;

use App\Services\BookingService;

class BookingController extends Controller
{
    public BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }
}
