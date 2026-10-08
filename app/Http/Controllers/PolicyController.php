<?php
// FILE: app/Http/Controllers/PolicyController.php
//
// Public policy pages linked from every footer and printed on receipts (autozenithparts.com/warranty).

namespace App\Http\Controllers;

class PolicyController extends Controller
{
    public function refund()   { return view('policies.refund'); }
    public function shipping() { return view('policies.shipping'); }
    public function warranty() { return view('policies.warranty'); }
}
