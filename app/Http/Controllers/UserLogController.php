<?php

namespace App\Http\Controllers;

use App\Exports\ArrayReportExport;
use App\Models\User;
use App\Models\UserLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class UserLogController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);

        $summaryQuery = clone $query;
        $logs = (clone $query)->with('user')->latest('occurred_at')->latest('id')->paginate(25)->withQueryString();

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'success' => (clone $summaryQuery)->where(function ($inner) {
                $inner->whereNull('status_code')->orWhere('status_code', '<', 400);
            })->count(),
            'failed' => (clone $summaryQuery)->where('status_code', '>=', 400)->count(),
            'users' => (clone $summaryQuery)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
        ];

        return view('user-logs.index', [
            'logs' => $logs,
            'summary' => $summary,
            'modules' => UserLog::query()->whereNotNull('module')->distinct()->orderBy('module')->pluck('module'),
            'actions' => UserLog::query()->whereNotNull('action')->distinct()->orderBy('action')->pluck('action'),
            'subjectTypes' => UserLog::query()->whereNotNull('auditable_type')->distinct()->orderBy('auditable_type')->pluck('auditable_type'),
            'users' => User::query()->orderBy('name')->limit(500)->get(['id', 'name', 'email']),
        ]);
    }

    public function export(Request $request)
    {
        $format = (string) $request->input('format', 'excel');
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);

        $rows = $this->exportRows($this->filteredQuery($request)->latest('occurred_at')->latest('id')->get());
        $headings = [
            'Date & Time',
            'User',
            'Email',
            'Operation',
            'Action Type',
            'Module',
            'Subject',
            'Page / Area',
            'Method',
            'Technical Route',
            'URL',
            'IP Address',
            'Status',
            'Description',
            'Request Data',
            'Old Values',
            'New Values',
            'Metadata',
            'User Agent',
        ];

        $filename = 'user_logs_'.now()->format('Ymd_His');

        if ($format === 'excel') {
            return Excel::download(new ArrayReportExport($headings, $rows->all(), 'User Logs'), $filename.'.xlsx');
        }

        return Pdf::loadView('reports.export_pdf', [
            'title' => 'User Logs',
            'headings' => $headings,
            'rows' => $rows,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download($filename.'.pdf');
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = UserLog::query();

        if ($search = trim((string) $request->input('search'))) {
            $routeSearch = $this->routeSearchFromReadableText($search);
            $actionSearch = $this->actionsFromReadableText($search);
            $urlPathSearch = $routeSearch !== '' ? str_replace('.', '/', $routeSearch) : '';

            $query->where(function ($inner) use ($search, $routeSearch, $urlPathSearch, $actionSearch) {
                $inner->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('auditable_label', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%")
                    ->orWhere('route_name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('user_agent', 'like', "%{$search}%");

                if ($routeSearch !== '') {
                    $inner->orWhere('route_name', 'like', "%{$routeSearch}%");
                }

                if ($urlPathSearch !== '') {
                    $inner->orWhere('url', 'like', "%{$urlPathSearch}%");
                }

                if ($actionSearch !== []) {
                    $inner->orWhereIn('action', $actionSearch);
                }
            });
        }

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        if ($request->filled('route_name')) {
            $query->where('route_name', 'like', '%'.trim((string) $request->input('route_name')).'%');
        }

        if ($request->filled('status_code')) {
            $query->where('status_code', $request->integer('status_code'));
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%'.trim((string) $request->input('ip_address')).'%');
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->input('auditable_type'));
        }

        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', $request->integer('auditable_id'));
        }

        if ($request->input('data_presence') === 'changes') {
            $query->where(function ($inner) {
                $inner->whereNotNull('old_values')->orWhereNotNull('new_values');
            });
        } elseif ($request->input('data_presence') === 'request') {
            $query->whereNotNull('request_data');
        }

        if ($request->filled('status')) {
            $request->input('status') === 'failed'
                ? $query->where('status_code', '>=', 400)
                : $query->where(function ($inner) {
                    $inner->whereNull('status_code')->orWhere('status_code', '<', 400);
                });
        }

        if ($request->filled('date_from')) {
            $query->where('occurred_at', '>=', $request->date('date_from')->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('occurred_at', '<=', $request->date('date_to')->endOfDay());
        }

        return $query;
    }

    private function exportRows(Collection $logs): Collection
    {
        return $logs->map(function (UserLog $log) {
            return [
                $log->occurred_at?->timezone(config('app.timezone'))->format('Y-m-d h:i:s A'),
                $log->user_name ?? 'System / Guest',
                $log->user_email,
                $log->operation_label,
                $log->action_label,
                $log->module,
                $log->subject_label,
                $log->location_label,
                $log->method,
                $log->route_name,
                $log->url,
                $log->ip_address,
                $log->status_code,
                $log->description,
                $this->jsonForExport($log->readable_request_data),
                $this->jsonForExport($log->readable_old_values),
                $this->jsonForExport($log->readable_new_values),
                $this->jsonForExport($log->readable_metadata),
                $log->user_agent,
            ];
        });
    }

    private function routeSearchFromReadableText(string $search): string
    {
        $value = Str::of($search)
            ->lower()
            ->replace(['opened ', 'viewed ', 'submitted ', 'downloaded ', 'created ', 'updated ', 'deleted ', 'restored ', 'failed '], '')
            ->replace([' for ', ' attempt ', ' form '], ' ')
            ->replace('user logs', 'user-logs')
            ->replace('direct resellers', 'direct-resellers')
            ->replace('direct reseller', 'direct-reseller')
            ->replace('reseller dues', 'reseller-dues')
            ->replace('reseller payments', 'reseller-payments')
            ->replace('bulk pdf download', 'bulk-pdf')
            ->replace('edit page', 'edit')
            ->replace('create page', 'create')
            ->replace('list', 'index')
            ->replace([' / ', '/', ' - ', '-'], '.')
            ->replaceMatches('/\s+/', '.')
            ->trim('.');

        return (string) $value;
    }

    private function actionsFromReadableText(string $search): array
    {
        $value = Str::lower($search);

        $actions = [];

        foreach ([
            'login' => ['login_succeeded', 'login_failed'],
            'logged in' => ['login_succeeded'],
            'failed login' => ['login_failed'],
            'logout' => ['logout'],
            'logged out' => ['logout'],
            'open' => ['viewed'],
            'view' => ['viewed'],
            'submit' => ['submitted', 'updated_request', 'deleted_request'],
            'download' => ['downloaded'],
            'export' => ['downloaded'],
            'create' => ['created', 'submitted'],
            'update' => ['updated', 'updated_request'],
            'delete' => ['deleted', 'deleted_request', 'force_deleted'],
            'restore' => ['restored'],
            'fail' => ['request_failed', 'login_failed'],
        ] as $needle => $matches) {
            if (str_contains($value, $needle)) {
                $actions = array_merge($actions, $matches);
            }
        }

        return array_values(array_unique($actions));
    }

    private function jsonForExport(mixed $value): string
    {
        if ($value === null || $value === [] || $value === '') {
            return '';
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
