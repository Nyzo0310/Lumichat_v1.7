<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Counselor;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CounselorController extends Controller
{
    // ==== Constants (dedupe + consistency) ====
    private const PER_PAGE     = 10;
    private const FLASH_SUCCESS = 'success';

    // ==== Views (optional constants keep things uniform) ====
    private const VIEW_INDEX  = 'admin.counselors.index';
    private const VIEW_CREATE = 'admin.counselors.create';
    private const VIEW_EDIT   = 'admin.counselors.edit';

    /**
     * List counselors with their availabilities (ordered).
     */
    public function index(): View
    {
        $counselors = Counselor::with([
                'availabilities' => function ($q) {
                    $q->orderBy('weekday')->orderBy('start_time');
                },
            ])
            ->latest()
            ->paginate(self::PER_PAGE);

        return view(self::VIEW_INDEX, compact('counselors'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view(self::VIEW_CREATE);
    }

    /**
     * Store a new counselor and their availability slots.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rulesStore());

        DB::transaction(function () use ($data) {
            $c = Counselor::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            foreach (($data['availability'] ?? []) as $slot) {
                $c->availabilities()->create($slot);
            }
        });

        return redirect()
            ->route('admin.counselors.index')
            ->with(self::FLASH_SUCCESS, 'Counselor added.');
    }

    /**
     * Show edit form.
     */
    public function edit(Counselor $counselor): View
    {
        $counselor->load('availabilities');

        return view(self::VIEW_EDIT, compact('counselor'));
    }

    /**
     * Update a counselor and replace availability slots (simple replace).
     */
    public function update(Request $request, Counselor $counselor): RedirectResponse
    {
        $data = $request->validate($this->rulesUpdate($counselor->id));

        DB::transaction(function () use ($counselor, $data) {
            $counselor->update([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            // Replace all slots (keeps your original approach/behavior)
            $counselor->availabilities()->delete();
            foreach (($data['availability'] ?? []) as $slot) {
                $counselor->availabilities()->create($slot);
            }
        });

        // Keep your original message text (no behavior change)
        return redirect()
            ->route('admin.counselors.index')
            ->with(self::FLASH_SUCCESS, 'Counselor added successfully!');
    }

    /**
     * Remove a counselor.
     */
    public function destroy(Counselor $counselor): RedirectResponse
    {
        $counselor->delete();

        return back()->with(self::FLASH_SUCCESS, 'Counselor removed.');
    }

    // ==== Private helpers (no logic change) ====

    /**
     * Validation rules for creating a counselor.
     */
    private function rulesStore(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:tbl_counselors,email'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'is_active'  => ['required', 'boolean'],

            // availability[]: [{ weekday, start_time, end_time }]
            'availability'              => ['array'],
            'availability.*.weekday'    => ['required', 'integer', 'between:0,6'],
            'availability.*.start_time' => ['required', 'date_format:H:i'],
            'availability.*.end_time'   => ['required', 'date_format:H:i', 'after:availability.*.start_time'],
        ];
    }

    /**
     * Validation rules for updating a counselor.
     */
    private function rulesUpdate(int $counselorId): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => [
                'required',
                'email',
                'max:255',
                Rule::unique('tbl_counselors', 'email')->ignore($counselorId),
            ],
            'phone'      => ['nullable', 'string', 'max:30'],
            'is_active'  => ['required', 'boolean'],

            'availability'              => ['array'],
            'availability.*.weekday'    => ['required', 'integer', 'between:0,6'],
            'availability.*.start_time' => ['required', 'date_format:H:i'],
            'availability.*.end_time'   => ['required', 'date_format:H:i', 'after:availability.*.start_time'],
        ];
    }
}
