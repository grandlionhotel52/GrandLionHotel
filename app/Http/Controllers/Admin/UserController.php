<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\AccountDirectory;
use App\Support\PersonName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim($request->string('q')->toString());
        $profile = $request->string('profile', 'all')->toString();
        $bookings = $request->string('bookings', 'all')->toString();

        if (!in_array($profile, ['all', 'complete', 'incomplete'], true)) {
            $profile = 'all';
        }

        if (!in_array($bookings, ['all', 'with', 'without'], true)) {
            $bookings = 'all';
        }

        $customersQuery = Customer::query()
            ->withCount(['bookings' => fn (Builder $query) => $query->visibleToOperations()])
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $nested) use ($keyword): void {
                    $nested->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('email', 'like', '%'.$keyword.'%')
                        ->orWhere('phone', 'like', '%'.$keyword.'%')
                        ->orWhere('city', 'like', '%'.$keyword.'%')
                        ->orWhere('province', 'like', '%'.$keyword.'%');
                });
            })
            ->when($profile === 'complete', function (Builder $query): void {
                $this->applyCompleteProfileFilter($query, true);
            })
            ->when($profile === 'incomplete', function (Builder $query): void {
                $this->applyCompleteProfileFilter($query, false);
            })
            ->when($bookings === 'with', function (Builder $query): void {
                $query->whereHas('bookings', fn (Builder $bookingQuery) => $bookingQuery->visibleToOperations());
            })
            ->when($bookings === 'without', function (Builder $query): void {
                $query->whereDoesntHave('bookings', fn (Builder $bookingQuery) => $bookingQuery->visibleToOperations());
            });

        $customers = $customersQuery
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $baseCustomerQuery = Customer::query();

        $stats = [
            'total_customers' => (clone $baseCustomerQuery)->count(),
            'with_bookings' => (clone $baseCustomerQuery)
                ->whereHas('bookings', fn (Builder $query) => $query->visibleToOperations())
                ->count(),
            'incomplete_profiles' => $this->countIncompleteProfiles((clone $baseCustomerQuery)),
            'recent_30_days' => (clone $baseCustomerQuery)
                ->whereDate('created_at', '>=', now()->subDays(30))
                ->count(),
        ];

        return view('admin.users.index', compact('customers', 'stats', 'profile', 'bookings'));
    }

    public function store(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (AccountDirectory::emailExists((string) $value)) {
                        $fail('This email is already registered.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\\-\\s]{7,30}$/'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        Customer::create([
            'name' => PersonName::combine($validated['first_name'], $validated['last_name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address_line' => $validated['address_line'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'country' => $validated['country'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Customer account created successfully.');
    }

    public function edit(Customer $user)
    {
        $user->loadCount('bookings');

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, Customer $user)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                    if (AccountDirectory::emailExists((string) $value, $user)) {
                        $fail('This email is already registered.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\\-\\s]{7,30}$/'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $data = [
            'name' => PersonName::combine($validated['first_name'], $validated['last_name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address_line' => $validated['address_line'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'country' => $validated['country'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Customer account updated successfully.');
    }

    public function toggleStatus(Customer $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Customer account {$status} successfully.");
    }

    private function applyCompleteProfileFilter(Builder $query, bool $complete): void
    {
        $requiredFields = ['name', 'email', 'phone', 'address_line', 'city', 'province'];

        if ($complete) {
            foreach ($requiredFields as $field) {
                $query->whereNotNull($field)->where($field, '!=', '');
            }

            return;
        }

        $query->where(function (Builder $nested) use ($requiredFields): void {
            foreach ($requiredFields as $field) {
                $nested->orWhereNull($field)->orWhere($field, '');
            }
        });
    }

    private function countIncompleteProfiles(Builder $query): int
    {
        $requiredFields = ['name', 'email', 'phone', 'address_line', 'city', 'province'];

        return $query->where(function (Builder $nested) use ($requiredFields): void {
            foreach ($requiredFields as $field) {
                $nested->orWhereNull($field)->orWhere($field, '');
            }
        })->count();
    }
}
