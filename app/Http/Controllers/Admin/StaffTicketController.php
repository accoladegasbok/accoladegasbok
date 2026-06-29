<?php
// FILE: app/Http/Controllers/Admin/StaffTicketController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewTicketMail;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class StaffTicketController extends Controller
{
    const CATEGORIES = ['Delete Invoice/Receipt', 'Edit Price', 'Approve Discount', 'Stock Issue', 'Account Request', 'Other'];

    // GET /admin/tickets — Admin/Manager see ALL pending; everyone else sees only their own
    public function index(Request $request)
    {
        $role = Session::get('staff_role');
        $isApprover = in_array($role, ['admin', 'manager']);

        $query = DB::table('staff_tickets as t')
            ->leftJoin('staff as raiser', 'raiser.id', '=', 't.raised_by_staff_id')
            ->leftJoin('staff as resolver', 'resolver.id', '=', 't.resolved_by_staff_id')
            ->select('t.*', 'raiser.name as raised_by_name', 'resolver.name as resolved_by_name')
            ->orderByRaw("CASE WHEN t.status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('t.created_at');

        if (!$isApprover) {
            $query->where('t.raised_by_staff_id', Session::get('staff_id'));
        } elseif ($status = $request->get('status')) {
            $query->where('t.status', $status);
        }

        $tickets = $query->paginate(30)->withQueryString();
        $pendingCount = DB::table('staff_tickets')->where('status', 'pending')->count();

        return view('admin.tickets.index', compact('tickets', 'isApprover', 'pendingCount'));
    }

    public function create()
    {
        return view('admin.tickets.create', ['categories' => self::CATEGORIES]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category'    => 'required|string',
            'subject'     => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
        ]);

        $year = date('Y');
        $seq  = DB::table('staff_tickets')->whereYear('created_at', $year)->count() + 1;
        $ticketNo = 'TKT-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $ticketId = DB::table('staff_tickets')->insertGetId([
            'ticket_no'           => $ticketNo,
            'raised_by_staff_id'  => Session::get('staff_id'),
            'category'            => $request->category,
            'subject'             => $request->subject,
            'description'         => $request->description,
            'reference_type'      => $request->reference_type,
            'reference_id'        => $request->reference_id,
            'status'              => 'pending',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // ── Alert admins/managers immediately by email + SMS, since
        // the requesting staff member can't act on this themselves —
        // they need someone with the right role to see it promptly,
        // not just wait for them to happen to check the ticket list.
        $ticket = DB::table('staff_tickets')->where('id', $ticketId)->first();
        $ticket->raised_by_name = Session::get('staff_name');

        $adminEmails = DB::table('staff')->whereIn('role', ['admin', 'manager'])
            ->where('is_active', true)->whereNotNull('email')->pluck('email');

        foreach ($adminEmails as $email) {
            try { Mail::to($email)->send(new NewTicketMail($ticket)); } catch (\Exception $e) { /* logged inside mailer config */ }
        }

        app(SmsService::class)->notifyAdminsAndManagers(
            "New ticket {$ticketNo}: {$request->subject} (raised by " . Session::get('staff_name') . "). Check the admin panel."
        );

        return redirect()->route('admin.tickets.index')->with('success', "Ticket {$ticketNo} submitted — admin/manager have been notified.");
    }

    public function show(int $id)
    {
        $ticket = DB::table('staff_tickets as t')
            ->leftJoin('staff as raiser', 'raiser.id', '=', 't.raised_by_staff_id')
            ->leftJoin('staff as resolver', 'resolver.id', '=', 't.resolved_by_staff_id')
            ->select('t.*', 'raiser.name as raised_by_name', 'resolver.name as resolved_by_name')
            ->where('t.id', $id)->first();
        abort_if(!$ticket, 404);

        $isApprover = in_array(Session::get('staff_role'), ['admin', 'manager']);
        if (!$isApprover && $ticket->raised_by_staff_id != Session::get('staff_id')) {
            abort(403);
        }

        return view('admin.tickets.show', compact('ticket', 'isApprover'));
    }

    // POST /admin/tickets/{id}/resolve — Admin/Manager only
    public function resolve(Request $request, int $id)
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            return back()->with('error', 'Only admin or manager can resolve tickets.');
        }

        $request->validate([
            'status'            => 'required|in:approved,rejected,completed',
            'resolution_notes'  => 'nullable|string|max:1000',
        ]);

        DB::table('staff_tickets')->where('id', $id)->update([
            'status'              => $request->status,
            'resolution_notes'    => $request->resolution_notes,
            'resolved_by_staff_id'=> Session::get('staff_id'),
            'resolved_at'         => now(),
            'updated_at'          => now(),
        ]);

        return redirect()->route('admin.tickets.index')->with('success', 'Ticket updated.');
    }
}
