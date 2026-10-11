<?php
namespace App\Http\Controllers;
use App\Models\{Commission,Customer,InventoryItem,Invoice,Payment,ServiceJob,User};
use App\Support\JobFilters;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OperationsController extends Controller {
    private array $serviceTypes = ['Repair & Diagnostics', 'Preventive Maintenance', 'New Installation', 'Inspection'];

    public function dashboard(Request $request)
    {
        $filters = JobFilters::validate($request);
        $filteredJobs = fn () => JobFilters::apply(ServiceJob::query(), $filters);
        $lowStockQuery = InventoryItem::whereColumn('stock', '<=', 'reorder_level');
        if (JobFilters::isActive($filters)) {
            $lowStockQuery->whereHas('jobParts', fn ($partQuery) => $partQuery->whereHas('job', fn ($jobQuery) => JobFilters::apply($jobQuery, $filters)));
        }

        return view('dashboard', [
            'jobsToday' => $filteredJobs()->count(),
            'activeJobs' => (clone $filteredJobs())->whereIn('status', ['Dispatched', 'In Progress'])->count(),
            'lowStock' => (clone $lowStockQuery)->count(),
            'revenue' => Invoice::where('status', 'Paid')->whereHas('job', fn ($jobQuery) => JobFilters::apply($jobQuery, $filters))->sum('total'),
            'upcoming' => JobFilters::apply(ServiceJob::with(['customer', 'technician']), $filters)->where('scheduled_at', '>=', now())->orderBy('scheduled_at')->take(6)->get(),
            'alerts' => $lowStockQuery->orderBy('stock')->take(5)->get(),
        ]);
    }
    public function customers(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $customersQuery = Customer::withCount('jobs')->latest();

        if ($search !== '') {
            $customersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('address', 'like', '%'.$search.'%');
            });
        }

        return view('customers.index', [
            'customers' => $customersQuery->paginate(10)->withQueryString(),
        ]);
    }

    public function storeCustomer(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:255',
            'address' => 'required|string',
            'type' => 'required|in:Residential,Commercial,Corporate',
        ]);

        DB::transaction(function () use ($data): void {
            $customerData = $data;
            unset($customerData['password']);
            Customer::create($customerData);
            User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => 'customer',
            ]);
        });

        return redirect()->route('customers.index')
            ->with('success', 'Customer saved.')
            ->with('customer_account_created', true);
    }

    public function updateCustomer(Request $request, Customer $customer)
    {
        $loginUser = $customer->email
            ? User::where('email', $customer->email)->where('role', 'customer')->first()
            : null;
        $data = $this->validatedCustomerData($request, $loginUser);

        DB::transaction(function () use ($customer, $loginUser, $data): void {
            $customer->update($data);
            $loginUser?->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);
        });

        return redirect()->route('customers.index')->with('success', 'Customer updated.');
    }

    public function deleteCustomer(Customer $customer)
    {
        if ($customer->jobs()->exists()) {
            return back()->withErrors([
                'customer' => 'This customer cannot be deleted because service jobs are linked to their account.',
            ]);
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }

    private function validatedCustomerData(Request $request, ?User $loginUser = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => [
                $loginUser ? 'required' : 'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($loginUser?->id),
            ],
            'address' => 'required|string',
            'type' => 'required|in:Residential,Commercial,Corporate',
        ]);
    }
    public function jobs(Request $request){
        $technicians = User::where('role','technician')->orderBy('name')->get();
        $busyJobsByTechnician = ServiceJob::whereNotNull('technician_id')
            ->whereIn('status', ServiceJob::ACTIVE_ASSIGNMENT_STATUSES)
            ->get(['id', 'technician_id', 'job_number', 'status'])
            ->groupBy('technician_id');
        $search = trim((string) $request->query('search', ''));
        $filters = JobFilters::validate($request);
        $jobsQuery = JobFilters::apply(ServiceJob::with(['customer', 'technician']), $filters)->latest('scheduled_at');
        if ($search !== '') {
            $jobsQuery->where(function ($query) use ($search) {
                $query->whereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('technician', fn ($technicianQuery) => $technicianQuery->where('name', 'like', '%'.$search.'%'));
            });
        }
        $techniciansByService = [];
        foreach ($this->serviceTypes as $serviceType) {
            $techniciansByService[$serviceType] = $technicians->filter(fn ($tech) => in_array($serviceType, (array) ($tech->service_types ?? []), true))->values();
        }
        return view('jobs.index',['jobs'=>$jobsQuery->paginate(12)->withQueryString(),'customers'=>Customer::orderBy('name')->get(),'technicians'=>$technicians,'serviceTypes'=>$this->serviceTypes,'techniciansByService'=>$techniciansByService,'busyJobsByTechnician'=>$busyJobsByTechnician]);
    }
    public function storeJob(Request $r){
        $d=$r->validate([
            'customer_id'=>'required|exists:customers,id',
            'technician_id'=>['nullable', Rule::exists('users', 'id')->where('role', 'technician')],
            'service_type'=>['required', Rule::in($this->serviceTypes)],
            'priority'=>'required',
            'notes'=>'nullable',
            'labor_cost'=>'required|numeric|min:0',
        ]);
        $d['scheduled_at'] = $this->validatedSchedule($r);
        $d['duration_minutes'] = ServiceJob::SLOT_DURATION_MINUTES;
        if (!empty($d['technician_id']) && !$this->technicianSupportsService((int) $d['technician_id'], $d['service_type'])) {
            return back()->withErrors(['technician_id'=>'Choose a technician qualified for the selected service type.'])->withInput();
        }
        if (!empty($d['technician_id']) && $this->technicianHasActiveJob((int) $d['technician_id'])) {
            return back()->withErrors(['technician_id'=>'This technician is busy doing another job. Choose an available technician or assign later.'])->withInput();
        }
        $d['job_number']=ServiceJob::nextJobNumber();
        ServiceJob::create($d);
        return back()->with('success','Service job booked and ready for dispatch.');
    }
    public function updateJob(Request $r, ServiceJob $job){
        $d=$r->validate([
            'customer_id'=>'required|exists:customers,id',
            'technician_id'=>['nullable', Rule::exists('users', 'id')->where('role', 'technician')],
            'service_type'=>['required', Rule::in($this->serviceTypes)],
            'priority'=>'required',
            'notes'=>'nullable',
            'labor_cost'=>'required|numeric|min:0',
        ]);
        $d['scheduled_at'] = $this->validatedSchedule($r);
        $d['duration_minutes'] = ServiceJob::SLOT_DURATION_MINUTES;
        if (!empty($d['technician_id']) && !$this->technicianSupportsService((int) $d['technician_id'], $d['service_type'])) {
            return back()->withErrors(['technician_id'=>'Choose a technician qualified for the selected service type.'])->withInput();
        }
        if (!empty($d['technician_id']) && $this->technicianHasActiveJob((int) $d['technician_id'], $job->id)) {
            return back()->withErrors(['technician_id'=>'This technician is busy doing another job. Choose an available technician or assign later.'])->withInput();
        }
        $job->update($d);
        if ($job->status === 'Completed') {
            $this->createBilling($job);
        }

        return back()->with('success','Service job updated.');
    }
    public function deleteJob(ServiceJob $job){$job->delete(); return back()->with('success','Service job deleted.');}
    private function validatedSchedule(Request $request): string
    {
        $data = $request->validate([
            'scheduled_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'time_slot' => ['required', Rule::in(array_keys(ServiceJob::TIME_SLOTS))],
        ]);

        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $data['scheduled_date'].' '.$data['time_slot']);
        if (!$scheduledAt->isFuture()) {
            throw ValidationException::withMessages([
                'time_slot' => 'Choose a future date and one of the available two-hour time slots.',
            ]);
        }

        return $scheduledAt->toDateTimeString();
    }
    public function storeTechnician(Request $r){
        $d=$r->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users,email',
            'phone'=>'nullable|string|max:50',
            'service_types'=>'required|array|min:1',
            'service_types.*'=>'in:'.implode(',', $this->serviceTypes),
        ]);

        $serviceTypes=array_values(array_unique(array_filter($d['service_types'])));

        User::create([
            'name'=>$d['name'],
            'email'=>$d['email'],
            'phone'=>$d['phone'] ?? null,
            'role'=>'technician',
            'password'=>bcrypt('TM3tech!'.random_int(1000, 9999)),
            'service_types'=>$serviceTypes,
        ]);

        return back()->with('success','Technician added.');
    }
    public function deleteTechnician(User $user){
        if ($user->role !== 'technician') {
            return back()->withErrors(['technician'=>'Only technicians can be removed here.']);
        }

        ServiceJob::where('technician_id', $user->id)->update(['technician_id' => null]);
        $user->delete();

        return back()->with('success','Technician deleted.');
    }
    public function updateJobStatus(Request $r, ServiceJob $job){$d=$r->validate(['status'=>'required|in:Pending,Dispatched,In Progress,Completed,Cancelled']);$job->update($d); if($d['status']==='Completed') $this->createBilling($job); return back()->with('success','Job status updated.');}
    private function technicianHasActiveJob(int $technicianId, ?int $exceptJobId = null): bool
    {
        return ServiceJob::where('technician_id', $technicianId)
            ->whereIn('status', ServiceJob::ACTIVE_ASSIGNMENT_STATUSES)
            ->when($exceptJobId, fn ($query) => $query->whereKeyNot($exceptJobId))
            ->exists();
    }
    private function technicianSupportsService(int $technicianId, string $serviceType): bool
    {
        $technician = User::where('role', 'technician')->find($technicianId);

        return $technician !== null && $technician->hasServiceType($serviceType);
    }
    public function inventory(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $availability = $request->query('availability', 'all');
        $itemsQuery = InventoryItem::withCount('jobParts')->orderBy('name');

        if ($search !== '') {
            $itemsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%');
            });
        }

        if ($availability === 'available') {
            $itemsQuery->where('stock', '>', 0)->whereColumn('stock', '>', 'reorder_level');
        } elseif ($availability === 'low') {
            $itemsQuery->where('stock', '>', 0)->whereColumn('stock', '<=', 'reorder_level');
        } elseif ($availability === 'out') {
            $itemsQuery->where('stock', 0);
        } else {
            $availability = 'all';
        }

        return view('inventory.index', [
            'items' => $itemsQuery->paginate(12)->withQueryString(),
            'availability' => $availability,
        ]);
    }

    public function storeInventory(Request $request)
    {
        $data = $this->validatedInventoryData($request);

        DB::transaction(function () use ($data) {
            $item = InventoryItem::create([
                ...$data,
                'sku' => 'TEMP-'.Str::uuid(),
            ]);

            $item->update([
                'sku' => 'SKU-'.str_pad((string) $item->id, 6, '0', STR_PAD_LEFT),
            ]);
        });

        return redirect()->route('inventory.index')->with('success', 'Inventory item added.');
    }

    public function updateInventory(Request $request, InventoryItem $item)
    {
        $item->update($this->validatedInventoryData($request));

        return redirect()->route('inventory.index')->with('success', 'Inventory item updated.');
    }

    public function deleteInventory(InventoryItem $item)
    {
        if ($item->jobParts()->exists()) {
            return back()->withErrors([
                'inventory' => 'This item cannot be deleted because it is used by a service job.',
            ]);
        }

        $item->delete();

        return redirect()->route('inventory.index')->with('success', 'Inventory item deleted.');
    }

    private function validatedInventoryData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'category' => ['required', Rule::in(InventoryItem::CATEGORIES)],
            'stock' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
        ]);
    }
    public function invoices(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $invoicesQuery = Invoice::with('job.customer')->latest();

        if ($search !== '') {
            $invoicesQuery->whereHas('job.customer', function ($customerQuery) use ($search) {
                $customerQuery->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        return view('invoices.index', [
            'invoices' => $invoicesQuery->paginate(12)->withQueryString(),
        ]);
    }
    public function updateInvoice(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);
        $this->recordInvoicePayment($invoice, (float) $data['amount'], $data['method'], $data['reference'] ?? null);

        return redirect()->route('invoices.index', $request->query())->with('success', 'Invoice payment updated.');
    }
    public function deleteInvoice(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }
    public function exportInvoices()
    {
        $invoices = Invoice::with(['job.customer', 'payments'])
            ->latest()
            ->get();
        $rows = [[
            ['Invoice', false],
            ['Date', false],
            ['Customer', false],
            ['Phone', false],
            ['Reported Issue', false],
            ['Total', false],
            ['Paid', false],
            ['Balance', false],
            ['Status', false],
            ['Payment Methods', false],
        ]];

        foreach ($invoices as $invoice) {
            $rows[] = [
                [$invoice->invoice_number, false],
                [$invoice->created_at?->format('Y-m-d') ?? '', false],
                [$invoice->job?->customer?->name ?? 'Unknown customer', false],
                [$invoice->job?->customer?->phone ?? '', false],
                [$invoice->job?->notes ?? '', false],
                [(float) $invoice->total, true],
                [(float) $invoice->paid_amount, true],
                [max(0, round((float) $invoice->total - (float) $invoice->paid_amount, 2)), true],
                [$invoice->status, false],
                [$invoice->payments->pluck('method')->unique()->implode(', '), false],
            ];
        }

        $sheetRows = '';
        foreach ($rows as $rowIndex => $row) {
            $cells = '';
            foreach ($row as $columnIndex => [$value, $numeric]) {
                $column = '';
                for ($number = $columnIndex + 1; $number > 0; $number = intdiv($number - 1, 26)) {
                    $column = chr(65 + (($number - 1) % 26)).$column;
                }
                $reference = $column.($rowIndex + 1);
                if ($numeric) {
                    $cells .= '<c r="'.$reference.'"><v>'.(float) $value.'</v></c>';
                } else {
                    $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $cells .= '<c r="'.$reference.'" t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
                }
            }
            $sheetRows .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetData>'.$sheetRows.'</sheetData></worksheet>';
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Billing" sheetId="1" r:id="rId1"/></sheets></workbook>';

        $temporaryFile = tempnam(sys_get_temp_dir(), 'billing-xlsx-');
        if ($temporaryFile === false) {
            throw new \RuntimeException('Unable to create a temporary Excel export file.');
        }

        try {
            $archive = new \ZipArchive();
            if ($archive->open($temporaryFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to initialize the Excel export file.');
            }

            $archive->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'</Types>');
            $archive->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>');
            $archive->addFromString('xl/workbook.xml', $workbook);
            $archive->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'</Relationships>');
            $archive->addFromString('xl/worksheets/sheet1.xml', $sheet);
            $archive->close();

            $content = file_get_contents($temporaryFile);
            if ($content === false) {
                throw new \RuntimeException('Unable to read the generated Excel export file.');
            }
        } finally {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="billing-report.xlsx"',
        ]);
    }
    public function pay(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);
        $this->recordInvoicePayment($invoice, (float) $data['amount'], $data['method'], $data['reference'] ?? null);

        return back()->with('success', 'Payment recorded.');
    }

    private function recordInvoicePayment(Invoice $invoice, float $amount, string $method, ?string $reference): void
    {
        DB::transaction(function () use ($invoice, $amount, $method, $reference): void {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $amount = round($amount, 2);
            $balance = max(0, round((float) $lockedInvoice->total - (float) $lockedInvoice->paid_amount, 2));

            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment cannot be greater than the remaining balance.',
                ]);
            }

            if ($amount > 0) {
                $lockedInvoice->payments()->create([
                    'amount' => $amount,
                    'method' => $method,
                    'reference' => $reference,
                    'paid_at' => now(),
                ]);
            }

            $paidAmount = round((float) $lockedInvoice->paid_amount + $amount, 2);
            $remainingBalance = max(0, round((float) $lockedInvoice->total - $paidAmount, 2));
            $status = $remainingBalance <= 0 ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Unpaid');

            $lockedInvoice->update([
                'paid_amount' => $paidAmount,
                'status' => $status,
            ]);
        });
    }
    public function updateCommission(Request $request, Commission $commission)
    {
        $commission->update($request->validate([
            'rate' => 'required|numeric|min:0|max:100',
            'amount' => 'required|numeric|min:0',
            'status' => ['required', Rule::in(['Pending', 'Paid'])],
        ]));

        return redirect()->route('reports.index', $request->query())->with('success', 'Commission entry updated.');
    }

    public function deleteCommission(Request $request, Commission $commission)
    {
        $commission->delete();

        return redirect()->route('reports.index', $request->query())->with('success', 'Commission entry deleted.');
    }

    public function reports(Request $request)
    {
        $filters = JobFilters::validate($request, 'month');
        $jobs = JobFilters::apply(ServiceJob::with(['customer', 'technician', 'invoice', 'parts.item']), $filters)
            ->orderByDesc('scheduled_at')
            ->get();
        $range = $filters['range'];
        $start = $filters['start'];
        $end = $filters['end'];
        $completedJobs = $jobs->where('status', 'Completed');
        $totalRevenue = $completedJobs->sum(fn (ServiceJob $job) => (float) ($job->invoice?->subtotal ?? 0));
        $cancelledJobs = $jobs->where('status', 'Cancelled')->count();
        $invoices = $completedJobs->pluck('invoice')->filter();

        $byService = $completedJobs->groupBy('service_type')
            ->map(fn ($serviceJobs, $serviceType) => (object) [
                'service_type' => $serviceType,
                'total' => $serviceJobs->count(),
            ])->sortByDesc('total')->values();
        $partsByCategory = $completedJobs->flatMap(fn (ServiceJob $job) => $job->parts)
            ->groupBy(fn ($part) => $part->item?->category ?: 'Other')
            ->map(fn ($parts, $category) => (object) [
                'category' => $category,
                'total' => $parts->sum(fn ($part) => (float) $part->quantity * (float) $part->unit_price),
            ])->sortByDesc('total')->values();
        $technicianPerformance = $completedJobs->groupBy(fn (ServiceJob $job) => $job->technician?->name ?? 'Unassigned')
            ->map(fn ($technicianJobs, $name) => (object) [
                'name' => $name,
                'jobs' => $technicianJobs->count(),
                'revenue' => $technicianJobs->sum(fn (ServiceJob $job) => (float) ($job->invoice?->subtotal ?? $job->labor_cost)),
            ])->sortByDesc('jobs')->values();
        $commissions = Commission::with(['technician', 'job.invoice'])
            ->whereIn('service_job_id', $jobs->pluck('id'))
            ->latest()
            ->get();
        $commissionsByJob = $commissions->keyBy('service_job_id');

        if ($request->query('export') === 'csv') {
            return response()->streamDownload(function () use ($jobs, $commissionsByJob) {
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Job ID', 'Scheduled At', 'Customer', 'Technician', 'Location', 'Service', 'Status', 'Labor', 'Parts', 'Subtotal', 'Commission']);
                foreach ($jobs as $job) {
                    $parts = $job->parts->sum(fn ($part) => (float) $part->quantity * (float) $part->unit_price);
                    fputcsv($output, [
                        $job->job_number,
                        $job->scheduled_at->format('Y-m-d H:i'),
                        $job->customer?->name ?? 'Unknown customer',
                        $job->technician?->name ?? 'Unassigned',
                        $job->customer?->address ?? '',
                        $job->service_type,
                        $job->status,
                        number_format((float) $job->labor_cost, 2, '.', ''),
                        number_format($parts, 2, '.', ''),
                        number_format((float) ($job->invoice?->subtotal ?? ((float) $job->labor_cost + $parts)), 2, '.', ''),
                        number_format((float) ($commissionsByJob->get($job->id)?->amount ?? 0), 2, '.', ''),
                    ]);
                }
                fclose($output);
            }, 'management-report.csv', ['Content-Type' => 'text/csv']);
        }

        return view('reports.index', [
            'jobs' => $jobs,
            'completed' => $completedJobs->count(),
            'totalRevenue' => $totalRevenue,
            'averageJobValue' => $invoices->isNotEmpty() ? $invoices->avg('subtotal') : 0,
            'cancelledRate' => $jobs->isNotEmpty() ? $cancelledJobs / $jobs->count() * 100 : 0,
            'noShowRate' => null,
            'byService' => $byService,
            'partsByCategory' => $partsByCategory,
            'technicianPerformance' => $technicianPerformance,
            'commissions' => $commissions,
            'technicians' => User::where('role', 'technician')->orderBy('name')->get(),
            'locations' => Customer::whereNotNull('address')->where('address', '!=', '')->distinct()->orderBy('address')->pluck('address'),
            'statuses' => ['Pending', 'Dispatched', 'In Progress', 'Completed', 'Cancelled'],
            'filters' => $filters,
            'range' => $range,
            'startDate' => $start?->toDateString() ?? 'All dates',
            'endDate' => $end?->toDateString() ?? '',
            'noShowStatusTracked' => false,
        ]);
    }
    private function createBilling(ServiceJob $job): void
    {
        $parts = (float) $job->parts()->sum(DB::raw('quantity * unit_price'));
        $subtotal = round((float) $job->labor_cost + $parts, 2);
        $tax = round($subtotal * 0.12, 2);
        $totals = [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => round($subtotal + $tax, 2),
        ];
        $invoice = $job->invoice()->first();

        if ($invoice) {
            $invoice->update($totals);
        } else {
            Invoice::create([
                'invoice_number' => 'INV-'.now()->format('ymd').'-'.str_pad((string) (Invoice::count() + 1), 3, '0', STR_PAD_LEFT),
                'service_job_id' => $job->id,
                ...$totals,
            ]);
        }

        if ($job->technician_id) {
            Commission::updateOrCreate(
                ['service_job_id' => $job->id],
                [
                    'technician_id' => $job->technician_id,
                    'rate' => 10,
                    'amount' => round($subtotal * 0.10, 2),
                ],
            );
        }
    }
}
