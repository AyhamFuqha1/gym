<?php

namespace App\Http\Controllers;

use App\DashBoardServices;
use App\Models\DashBoard;
use Illuminate\Http\Request;

class DashBoardController extends Controller
{
    private $dashboard;

    public function __construct(DashBoardServices $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    public function dashboard()
    {
        return response()->json($this->dashboard->dashboard(), 200);
    }
}
