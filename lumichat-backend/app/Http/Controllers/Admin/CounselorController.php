<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CounselorRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CounselorController extends Controller
{
    // ==== Constants (dedupe + consistency) ====
    private const PER_PAGE      = 10;
    private const FLASH_SUCCESS = 'success';

    // ==== Views ====
    private const VIEW_INDEX  = 'admin.counselors.index';
    private const VIEW_CREATE = 'admin.counselors.create';
    private const VIEW_EDIT   = 'admin.counselors.edit';

    public function __construct(
        protected CounselorRepositoryInterface $counselors
    ) {}

    /**
     * List counselors with their availabilities (ordered).
     */
    public function index(): View
    {
        $counselors = $this->counselors->paginateWithFilters([
            'with_availabilities_ordered' => true,
        ], self::PER_PAGE);

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

        $this->counselors->create($data);

        return redirect()
            ->route('admin.counselors.index')
            ->with(self::FLASH_SUCCESS, 'Counselor added.');
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): View
    {
        $counselor = $this->counselors->findById($id, ['availabilities']);
        abort_if(!$counselor, 404);

        return view(self::VIEW_EDIT, compact('counselor'));
    }

    /**
     * Update a counselor and replace availability slots (simple replace).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate($this->rulesUpdate($id));

        $this->counselors->update($id, $data);

        return redirect()
            ->route('admin.counselors.index')
            ->with(self::FLASH_SUCCESS, 'Counselor added successfully!');
    }

    /**
     * Remove a counselor.
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->counselors->delete($id);

        return back()->with(self::FLASH_SUCCESS, 'Counselor removed.');
    }

    // ==== Private helpers (kept from your original) ====

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
