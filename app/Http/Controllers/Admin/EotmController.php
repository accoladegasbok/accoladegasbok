<?php
// FILE: app/Http/Controllers/Admin/EotmController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmployeeOfMonth;
use App\Services\TrainingCompliance;
use App\Support\StaffRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EotmController extends Controller
{
    private function me(): int { return (int) session('staff_id'); }
    private function isManager(): bool { return in_array(session('staff_role'), ['admin', 'manager'], true); }

    private function ym(Request $r): string
    {
        $v = (string) $r->query('ym', $r->input('ym', now()->format('Y-m')));
        return preg_match('/^\d{4}-\d{2}$/', $v) ? $v : now()->format('Y-m');
    }

    private function cycle(string $ym): object
    {
        $c = DB::table('eotm_cycles')->where('ym', $ym)->first();
        if (!$c) {
            $id = DB::table('eotm_cycles')->insertGetId(['ym' => $ym, 'status' => 'rating', 'created_at' => now(), 'updated_at' => now()]);
            $c = DB::table('eotm_cycles')->where('id', $id)->first();
        }
        return $c;
    }

    public function index(Request $r)
    {
        if (!EmployeeOfMonth::ready()) return view('admin.eotm.not-ready');
        $ym = $this->ym($r);
        $cycle = $this->cycle($ym);

        $nominees = DB::table('eotm_nominees as n')->join('staff as s', 's.id', '=', 'n.staff_id')
            ->where('n.cycle_id', $cycle->id)->select('n.*', 's.name', 's.location', 's.role')->orderBy('n.rank')->get()
            ->map(function ($n) { $n->breakdown = json_decode($n->breakdown, true) ?: []; return $n; });
        $myVote = DB::table('eotm_votes')->where('cycle_id', $cycle->id)->where('voter_staff_id', $this->me())->value('nominee_staff_id');

        $scores = $this->isManager() && $cycle->status === 'rating' ? EmployeeOfMonth::compute($ym) : null;
        $winner = $cycle->winner_staff_id ? DB::table('staff')->where('id', $cycle->winner_staff_id)->first() : null;
        $history = DB::table('eotm_cycles as c')->join('staff as s', 's.id', '=', 'c.winner_staff_id')
            ->where('c.status', 'closed')->orderByDesc('c.ym')->limit(12)->get(['c.ym', 'c.id', 'c.paid_at', 'c.prize_amount', 'c.prize_currency', 's.name', 's.location']);
        $votesCast = DB::table('eotm_votes')->where('cycle_id', $cycle->id)->count();
        $eligibleVoters = DB::table('staff')->where('is_active', true)->count();

        return view('admin.eotm.index', [
            'ym' => $ym, 'cycle' => $cycle, 'nominees' => $nominees, 'myVote' => $myVote, 'scores' => $scores, 'winner' => $winner,
            'history' => $history, 'isMgr' => $this->isManager(), 'isSup' => StaffRole::isSupervisorOrAbove(),
            'votesCast' => $votesCast, 'eligibleVoters' => $eligibleVoters, 'criteria' => EmployeeOfMonth::CRITERIA,
            'rewards' => json_decode($cycle->rewards ?? '[]', true) ?: [],
        ]);
    }

    // ── Supervisor ratings (the four criteria the system cannot measure) ──

    public function rateForm(Request $r)
    {
        abort_unless(StaffRole::isSupervisorOrAbove(), 403);
        if (!EmployeeOfMonth::ready()) return view('admin.eotm.not-ready');
        $ym = $this->ym($r);
        $q = DB::table('staff')->where('is_active', true)->where('role', '!=', 'admin')->where('id', '!=', $this->me());
        if (!$this->isManager()) $q->where('location', session('staff_location'));
        $staff = $q->orderBy('location')->orderBy('name')->get();
        $mine = DB::table('eotm_ratings')->where('ym', $ym)->where('rater_id', $this->me())->get()->keyBy('staff_id');
        return view('admin.eotm.rate', compact('ym', 'staff', 'mine'));
    }

    public function rateSave(Request $r)
    {
        abort_unless(StaffRole::isSupervisorOrAbove(), 403);
        $ym = $this->ym($r);
        $cycle = $this->cycle($ym);
        if ($cycle->status !== 'rating') return back()->with('error', 'Ratings are closed for ' . $ym . ' because the shortlist is already made.');

        $allowed = DB::table('staff')->where('is_active', true)->where('id', '!=', $this->me());
        if (!$this->isManager()) $allowed->where('location', session('staff_location'));
        $allowed = $allowed->pluck('id')->all();

        $n = 0;
        foreach ((array) $r->input('r', []) as $staffId => $vals) {
            $staffId = (int) $staffId;
            if (!in_array($staffId, $allowed, true)) continue;
            $row = [];
            foreach (EmployeeOfMonth::RATING_KEYS as $k) {
                $v = (int) ($vals[$k] ?? 0);
                $row[$k] = ($v >= 1 && $v <= 5) ? $v : null;
            }
            if (!array_filter($row)) continue;
            $row['comment'] = substr(trim((string) ($vals['comment'] ?? '')), 0, 255) ?: null;
            $exists = DB::table('eotm_ratings')->where(['ym' => $ym, 'staff_id' => $staffId, 'rater_id' => $this->me()])->exists();
            if ($exists) DB::table('eotm_ratings')->where(['ym' => $ym, 'staff_id' => $staffId, 'rater_id' => $this->me()])->update($row + ['updated_at' => now()]);
            else DB::table('eotm_ratings')->insert($row + ['ym' => $ym, 'staff_id' => $staffId, 'rater_id' => $this->me(), 'created_at' => now(), 'updated_at' => now()]);
            $n++;
        }
        return redirect()->route('admin.eotm.rate', ['ym' => $ym])->with('success', "Ratings saved for {$n} staff.");
    }

    // ── Manager: shortlist, vote, close, rewards ──

    public function shortlist(Request $r)
    {
        abort_unless($this->isManager(), 403);
        $ym = $this->ym($r);
        $cycle = $this->cycle($ym);
        if ($cycle->status !== 'rating') return back()->with('error', 'The shortlist was already made.');

        $rank = EmployeeOfMonth::compute($ym)->where('eligible', true)->sortByDesc('perf')->values()->take(3);
        if ($rank->count() < 2) return back()->with('error', 'Fewer than 2 eligible staff this month. Add ratings or check training, then try again.');

        DB::transaction(function () use ($cycle, $rank) {
            foreach ($rank as $i => $x) {
                DB::table('eotm_nominees')->insert([
                    'cycle_id' => $cycle->id, 'staff_id' => $x->staff->id, 'rank' => $i + 1, 'performance_score' => $x->perf,
                    'breakdown' => json_encode(['scores' => $x->scores, 'coverage' => $x->coverage]), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('eotm_cycles')->where('id', $cycle->id)->update(['status' => 'voting', 'shortlisted_at' => now(), 'updated_at' => now()]);
        });
        return redirect()->route('admin.eotm.index', ['ym' => $ym])->with('success', 'Top 3 nominees chosen. Voting is open to all staff.');
    }

    public function vote(Request $r)
    {
        $ym = $this->ym($r);
        $cycle = $this->cycle($ym);
        if ($cycle->status !== 'voting') return back()->with('error', 'Voting is not open.');
        $nid = (int) $r->input('nominee');
        if (!DB::table('eotm_nominees')->where('cycle_id', $cycle->id)->where('staff_id', $nid)->exists()) return back()->with('error', 'Pick one of the three nominees.');
        if ($nid === $this->me()) return back()->with('error', 'You cannot vote for yourself.');
        if (DB::table('eotm_votes')->where('cycle_id', $cycle->id)->where('voter_staff_id', $this->me())->exists()) return back()->with('error', 'You have already voted. One vote per person.');
        try {
            DB::table('eotm_votes')->insert(['cycle_id' => $cycle->id, 'voter_staff_id' => $this->me(), 'nominee_staff_id' => $nid, 'created_at' => now(), 'updated_at' => now()]);
        } catch (\Throwable $e) {
            return back()->with('error', 'You have already voted.');
        }
        return back()->with('success', 'Your vote is recorded. Thank you.');
    }

    public function close(Request $r)
    {
        abort_unless($this->isManager(), 403);
        $ym = $this->ym($r);
        $cycle = $this->cycle($ym);
        if ($cycle->status !== 'voting') return back()->with('error', 'Voting is not open.');

        $nominees = DB::table('eotm_nominees')->where('cycle_id', $cycle->id)->get();
        $total = DB::table('eotm_votes')->where('cycle_id', $cycle->id)->count();
        $best = null;
        foreach ($nominees as $n) {
            $votes = DB::table('eotm_votes')->where('cycle_id', $cycle->id)->where('nominee_staff_id', $n->staff_id)->count();
            $share = $total > 0 ? round(100 * $votes / $total, 1) : 0;
            $final = round(EmployeeOfMonth::PERFORMANCE_SHARE * $n->performance_score + EmployeeOfMonth::VOTE_SHARE * $share, 1);
            $dq = TrainingCompliance::stateFor($n->staff_id) === 'red';
            DB::table('eotm_nominees')->where('id', $n->id)->update([
                'votes' => $votes, 'vote_share' => $share, 'final_score' => $final, 'disqualified' => $dq,
                'disqualified_reason' => $dq ? 'Training overdue' : null, 'updated_at' => now(),
            ]);
            if (!$dq && (!$best || $final > $best['final'] || ($final == $best['final'] && $n->performance_score > $best['perf']))) {
                $best = ['id' => $n->staff_id, 'final' => $final, 'perf' => $n->performance_score];
            }
        }
        if (!$best) return back()->with('error', 'All nominees are ineligible because of overdue training. Clear it first, then close.');

        DB::table('eotm_cycles')->where('id', $cycle->id)->update(['status' => 'closed', 'winner_staff_id' => $best['id'], 'closed_at' => now(), 'updated_at' => now()]);
        return redirect()->route('admin.eotm.index', ['ym' => $ym])->with('success', 'Voting closed. Winner declared.');
    }

    public function reward(Request $r)
    {
        abort_unless($this->isManager(), 403);
        $ym = $this->ym($r);
        $cycle = $this->cycle($ym);
        abort_unless($cycle->status === 'closed', 403);
        $d = $r->validate(['prize_amount' => 'nullable|numeric|min:0', 'prize_currency' => 'nullable|in:NGN,USD,GHS', 'prize_note' => 'nullable|string|max:255',
                           'rewards' => 'nullable|array', 'rewards.*' => 'in:cash,voucher,salary,certificate,promotion']);
        DB::table('eotm_cycles')->where('id', $cycle->id)->update([
            'rewards' => json_encode(array_values($d['rewards'] ?? [])), 'prize_amount' => $d['prize_amount'] ?? null,
            'prize_currency' => $d['prize_currency'] ?? null, 'prize_note' => $d['prize_note'] ?? null,
            'paid_at' => $r->boolean('paid') ? ($cycle->paid_at ?: now()) : null, 'updated_at' => now(),
        ]);
        return back()->with('success', 'Rewards saved.');
    }

    public function certificate(int $cycleId)
    {
        $cycle = DB::table('eotm_cycles')->where('id', $cycleId)->where('status', 'closed')->first();
        abort_unless($cycle && $cycle->winner_staff_id, 404);
        $winner = DB::table('staff')->where('id', $cycle->winner_staff_id)->first();
        $nom = DB::table('eotm_nominees')->where('cycle_id', $cycle->id)->where('staff_id', $cycle->winner_staff_id)->first();
        return view('admin.eotm.certificate', compact('cycle', 'winner', 'nom'));
    }
}
