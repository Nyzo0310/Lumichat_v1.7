@extends('layouts.admin')
@section('title','Counselors')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">
  {{-- Page header --}}
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold tracking-tight text-slate-800">Counselors</h2>
      <p class="text-sm text-slate-500">Manage counselor profiles and weekly availability.</p>
    </div>
    <a href="{{ route('admin.counselors.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl shadow-sm hover:bg-indigo-700 active:scale-[.99] transition">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Add Counselor
    </a>
  </div>

  {{-- Table card --}}
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm table-auto">
        <thead class="bg-slate-200 text-slate-800 shadow-sm">
          <tr class="align-middle">
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap min-w-[170px]">
              Counselor Name
            </th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Email</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Contact No.</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Status</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Available Time</th>
            <th class="px-6 py-3 text-right font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          @forelse ($counselors as $c)
            <tr class="hover:bg-slate-50/70 align-middle">
              {{-- Counselor name (name only; no subtitle) --}}
              <td class="px-6 py-4 whitespace-nowrap text-slate-900 font-semibold">
                {{ $c->name }}
              </td>

              {{-- Email --}}
              <td class="px-6 py-4 text-slate-700 whitespace-nowrap">{{ $c->email }}</td>

              {{-- Contact --}}
              <td class="px-6 py-4 text-slate-700 whitespace-nowrap">{{ $c->phone ?? '—' }}</td>

              {{-- Status (single-line chip) --}}
              <td class="px-6 py-4 whitespace-nowrap">
                @if($c->is_active)
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70 whitespace-nowrap">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Available
                  </span>
                @else
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs bg-rose-50 text-rose-700 ring-1 ring-rose-200/70 whitespace-nowrap">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    Not Available
                  </span>
                @endif
              </td>

              {{-- Availability chips --}}
              <td class="px-6 py-4">
                @php $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; @endphp
                <div class="flex flex-wrap gap-1.5">
                  @forelse ($c->availabilities->groupBy('weekday') as $weekday => $slots)
                    <span
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs
                             bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/70
                             whitespace-nowrap hover:ring-indigo-300 transition">
                      <strong class="font-semibold">{{ $days[$weekday] }}:</strong>
                      @foreach ($slots as $slot)
                        <span class="opacity-90">{{ substr($slot->start_time,0,5) }}–{{ substr($slot->end_time,0,5) }}</span>@if(!$loop->last),@endif
                      @endforeach
                    </span>
                  @empty
                    <span class="text-slate-400 text-xs">No slots</span>
                  @endforelse
                </div>
              </td>

              {{-- Actions --}}
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                  <a href="{{ route('admin.counselors.edit',$c) }}"
                     class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 hover:ring-slate-300 active:scale-[.97] transition"
                     title="Edit">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5h2m-7 8l-1 4 4-1 9-9a2 2 0 10-3-3l-9 9z"/>
                    </svg>
                  </a>

                  <form id="delete-form-{{ $c->id }}"
                        action="{{ route('admin.counselors.destroy',$c) }}"
                        method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="button"
                            onclick="confirmDelete({{ $c->id }})"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-rose-600/10 text-rose-700 ring-1 ring-rose-200 hover:bg-rose-600/15 hover:ring-rose-300 active:scale-[.97] transition"
                            title="Delete">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0V5a2 2 0 012-2h2a2 2 0 012 2v2"/>
                      </svg>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-6 py-10 text-center">
                <div class="text-slate-500">No counselors yet.</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($counselors->hasPages())
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/70">
        {{ $counselors->links() }}
      </div>
    @endif
  </div>
</div>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  @if (session('success'))
    Swal.fire({
      title: 'Success',
      text: @json(session('success')),
      icon: 'success',
      confirmButtonColor: '#4f46e5'
    });
  @endif

  @if (session('error'))
    Swal.fire({
      title: 'Error',
      text: @json(session('error')),
      icon: 'error',
      confirmButtonColor: '#ef4444'
    });
  @endif

  function confirmDelete(id) {
    Swal.fire({
      title: 'Delete counselor?',
      text: 'This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#6b7280'
    }).then((result) => {
      if (result.isConfirmed) {
        document.getElementById('delete-form-' + id).submit();
      }
    });
  }
</script>
@endsection
