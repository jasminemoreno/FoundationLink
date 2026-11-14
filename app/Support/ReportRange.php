<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The "From / To" dates chosen on a report page. Each date is a whole day in the
 * users' local time (Philippines by default); no dates means all time.
 */
class ReportRange
{
    /** Most monthly bars a report chart shows (the latest months of a longer range) */
    public const MAX_MONTHS = 24;

    private function __construct(
        public readonly ?Carbon $from, // first moment, local time
        public readonly ?Carbon $to,   // last moment, local time
    ) {}

    public static function fromRequest(Request $request): self
    {
        $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            // compared only when a start date was sent: after_or_equal on a missing field always fails
            'to' => array_merge(['nullable', 'date_format:Y-m-d'], $request->filled('from') ? ['after_or_equal:from'] : []),
        ], [
            'to.after_or_equal' => 'The end date must be on or after the start date.',
        ]);

        $tz = config('app.display_timezone');

        return new self(
            $request->filled('from') ? Carbon::createFromFormat('Y-m-d', $request->input('from'), $tz)->startOfDay() : null,
            $request->filled('to') ? Carbon::createFromFormat('Y-m-d', $request->input('to'), $tz)->endOfDay() : null,
        );
    }

    /** When a donation happened: donated_at, or created_at for older rows */
    public static function donationDate(string $table = 'donations')
    {
        return DB::raw("COALESCE({$table}.donated_at, {$table}.created_at)");
    }

    public function isAllTime(): bool
    {
        return !$this->from && !$this->to;
    }

    /** Limit a query to rows whose $column (stored in app time) falls in the range */
    public function apply($query, $column)
    {
        $appTz = config('app.timezone');

        return $query
            ->when($this->from, fn($q) => $q->where($column, '>=', $this->from->copy()->setTimezone($appTz)))
            ->when($this->to, fn($q) => $q->where($column, '<=', $this->to->copy()->setTimezone($appTz)));
    }

    /** Whether a date (e.g. an already-loaded donation's) falls in the range */
    public function contains($date): bool
    {
        if (!$date) {
            return false;
        }
        $d = Carbon::parse($date)->setTimezone(config('app.display_timezone'));

        return (!$this->from || $d->gte($this->from)) && (!$this->to || $d->lte($this->to));
    }

    /** The part of this range inside [$start, $end], e.g. one month of a chart */
    public function within(Carbon $start, Carbon $end): self
    {
        return new self(
            $this->from && $this->from->gt($start) ? $this->from : $start,
            $this->to && $this->to->lt($end) ? $this->to : $end,
        );
    }

    /**
     * First day (local time) of each month a per-month chart shows: the range's
     * months, or the last $default months up to today when there is no start date.
     *
     * @return Carbon[]
     */
    public function months(int $default = 6): array
    {
        $tz = config('app.display_timezone');
        $end = ($this->to ?? now($tz))->copy()->startOfMonth();
        $start = $this->from
            ? $this->from->copy()->startOfMonth()
            : $end->copy()->subMonthsNoOverflow($default - 1);

        if ($start->gt($end)) {
            return [$start];
        }

        $months = [];
        for ($m = $start->copy(); $m->lte($end); $m->addMonthNoOverflow()) {
            $months[] = $m->copy();
        }

        return array_slice($months, -self::MAX_MONTHS);
    }

    /** One month of the chart: its days that are also in the range */
    public function month(Carbon $monthStart): self
    {
        return $this->within($monthStart->copy()->startOfMonth(), $monthStart->copy()->endOfMonth());
    }

    /** e.g. "Nov 1, 2026 – Nov 15, 2026", or "all time" */
    public function label(): string
    {
        $day = fn(Carbon $d) => $d->format('M j, Y');

        return match (true) {
            $this->from && $this->to => $day($this->from) === $day($this->to)
                ? $day($this->from)
                : $day($this->from) . ' – ' . $day($this->to),
            (bool) $this->from => 'since ' . $day($this->from),
            (bool) $this->to => 'until ' . $day($this->to),
            default => 'all time',
        };
    }
}
