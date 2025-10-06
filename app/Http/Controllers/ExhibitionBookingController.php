<?php

namespace App\Http\Controllers;

use App\Models\Exhibition;
use Illuminate\Http\Request;

class ExhibitionBookingController extends Controller
{
    public function show(Request $request, Exhibition $exhibition)
    {
        return view('exhibitions.booking', compact('exhibition'));
    }
}
