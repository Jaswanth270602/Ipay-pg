<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Resellers\StoreResellerRequest;
use App\Http\Requests\Admin\Resellers\UpdateResellerRequest;
use App\Models\Reseller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResellersController extends Controller
{
    public function index(): View
    {
        return view('admin.resellers.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 10), 50);
        $query = Reseller::query()->latest();

        if ($request->filled('filter_name')) {
            $query->where('name', 'like', '%' . trim((string) $request->get('filter_name')) . '%');
        }
        if ($request->filled('filter_email')) {
            $query->where('email', 'like', '%' . trim((string) $request->get('filter_email')) . '%');
        }
        if ($request->filled('filter_phone')) {
            $query->where('phone', 'like', '%' . trim((string) $request->get('filter_phone')) . '%');
        }
        if ($request->filled('filter_company_name')) {
            $query->where('company_name', 'like', '%' . trim((string) $request->get('filter_company_name')) . '%');
        }
        if ($request->filled('filter_status') && $request->get('filter_status') !== 'all') {
            $query->where('status', $request->get('filter_status'));
        }

        $resellers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $resellers->items(),
            'pagination' => [
                'current_page' => $resellers->currentPage(),
                'per_page' => $resellers->perPage(),
                'total' => $resellers->total(),
                'last_page' => $resellers->lastPage(),
                'from' => $resellers->firstItem(),
                'to' => $resellers->lastItem(),
            ],
        ]);
    }

    public function show(int $id): View
    {
        $reseller = Reseller::findOrFail($id);

        $totalMerchants = $reseller->merchants()->count();

        $stats = [
            'total_merchants' => $totalMerchants,
            'total_transactions' => 0,
            'total_volume' => 0,
            'total_earnings' => 0,
        ];

        $assignedMerchants = $reseller->merchants()
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'email', 'status']);

        return view('admin.resellers.show', [
            'reseller' => $reseller,
            'stats' => $stats,
            'assignedMerchants' => $assignedMerchants,
        ]);
    }

    public function getOne(int $id): JsonResponse
    {
        $reseller = Reseller::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $reseller,
        ]);
    }

    public function store(StoreResellerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {
            $reseller = Reseller::create([
                'reseller_unique_id' => $this->nextResellerUniqueId(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'company_name' => $validated['company_name'],
                'status' => (bool) $validated['status'] ? 'active' : 'inactive',
                'commission_type' => $validated['commission_type'] ?? 'percentage',
                'commission_value' => $validated['commission_value'] ?? 0,
            ]);

            $resellerRole = Role::query()->where('name', 'reseller')->first();
            if (! $resellerRole) {
                abort(500, 'Reseller role not found. Please seed roles.');
            }

            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $resellerRole->id,
                'reseller_id' => $reseller->id,
                'status' => (bool) $validated['status'] ? 'active' : 'inactive',
                'email_verified_at' => now(),
                'timezone' => 'Asia/Kolkata',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reseller created successfully.',
                'data' => $reseller,
            ]);
        });
    }

    public function update(UpdateResellerRequest $request, int $id): JsonResponse
    {
        $reseller = Reseller::findOrFail($id);
        $validated = $request->validated();

        $user = User::query()->where('reseller_id', $reseller->id)->first();
        if ($user && $validated['email'] !== $user->email) {
            $emailTaken = User::query()
                ->where('email', $validated['email'])
                ->where('id', '!=', $user->id)
                ->exists();
            if ($emailTaken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['email' => ['The email has already been taken.']],
                ], 422);
            }
        }

        return DB::transaction(function () use ($reseller, $validated, $user) {
            $reseller->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'company_name' => $validated['company_name'],
                'status' => (bool) $validated['status'] ? 'active' : 'inactive',
                'commission_type' => $validated['commission_type'] ?? 'percentage',
                'commission_value' => $validated['commission_value'] ?? 0,
            ]);

            if ($user) {
                $user->name = $validated['name'];
                $user->email = $validated['email'];
                $user->status = (bool) $validated['status'] ? 'active' : 'inactive';
                if (! empty($validated['password'])) {
                    $user->password = Hash::make($validated['password']);
                }
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Reseller updated successfully.',
                'data' => $reseller->fresh(),
            ]);
        });
    }

    public function destroy(int $id): JsonResponse
    {
        $reseller = Reseller::findOrFail($id);

        return DB::transaction(function () use ($reseller) {
            User::query()->where('reseller_id', $reseller->id)->delete();
            $reseller->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reseller deleted successfully.',
            ]);
        });
    }

    protected function nextResellerUniqueId(): string
    {
        do {
            $candidate = 'IPAY_RID_' . strtoupper(Str::random(8));
        } while (Reseller::query()->where('reseller_unique_id', $candidate)->exists());

        return $candidate;
    }
}

