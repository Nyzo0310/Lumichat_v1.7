@extends('layouts.admin')
@section('title','Admin · Counselors')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">
  {{-- Page header --}}
  @php
    $totalCounselors = method_exists($counselors, 'total') ? $counselors->total() : $counselors->count();
  @endphp
  <div class="flex items-center justify-between animate-fadeup screen-only">
    <div>
      <h2 class="text-2xl font-bold tracking-tight text-slate-800">Counselors</h2>
      <p class="text-sm text-slate-500">
        Manage counselor profiles and weekly availability.
        <span class="ml-2 text-slate-400">•</span>
        <span class="ml-2 text-slate-600">
          {{ $totalCounselors }} {{ Str::plural('counselor', $totalCounselors) }}
        </span>
      </p>
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
    <div class="relative overflow-x-auto">
      <table class="min-w-full text-sm leading-6">
        <thead class="bg-slate-100 border-b border-slate-200 text-slate-700">
          <tr class="align-middle">
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap min-w-[170px]">Counselor Name</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Email</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Contact No.</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Status</th>
            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Available Time</th>
            <th class="px-6 py-3 text-right font-semibold uppercase tracking-wide text-[11px] whitespace-nowrap">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          @forelse ($counselors as $c)
            <tr class="align-middle even:bg-slate-50 hover:bg-slate-100/60 transition">
              {{-- Counselor name --}}
              <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-900">{{ $c->name }}</td>

              {{-- Email --}}
              <td class="px-6 py-4 text-slate-700 truncate max-w-[240px]">{{ $c->email }}</td>

              {{-- Contact --}}
              <td class="px-6 py-4 text-slate-700 whitespace-nowrap">{{ $c->phone ?? '—' }}</td>

              {{-- Status --}}
              <td class="px-6 py-4 whitespace-nowrap">
                @if($c->is_active)
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Available
                  </span>
                @else
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs bg-rose-50 text-rose-700 ring-1 ring-rose-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Not Available
                  </span>
                @endif
              </td>

              {{-- Availability --}}
              <td class="px-6 py-4">
                @php $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; @endphp
                <div class="flex flex-wrap gap-1.5">
                  @forelse ($c->availabilities->groupBy('weekday') as $weekday => $slots)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200 whitespace-nowrap">
                      <strong>{{ $days[$weekday] }}:</strong>
                      @foreach ($slots as $slot)
                        {{ substr($slot->start_time,0,5) }}–{{ substr($slot->end_time,0,5) }}@if(!$loop->last),@endif
                      @endforeach
                    </span>
                  @empty
                    <span class="text-slate-400 text-xs">No slots</span>
                  @endforelse
                </div>
              </td>

              {{-- Actions --}}
              <td class="px-6 py-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  <a href="{{ route('admin.counselors.edit',$c) }}"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 hover:ring-slate-300 active:scale-[.97] transition"
                    title="Edit">
                    ✏️
                  </a>
                  <form id="delete-form-{{ $c->id }}" action="{{ route('admin.counselors.destroy',$c) }}" method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="button" onclick="confirmDelete({{ $c->id }})"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-rose-600/10 text-rose-700 ring-1 ring-rose-200 hover:bg-rose-600/15 hover:ring-rose-300 active:scale-[.97] transition"
                            title="Delete">
                      🗑️
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-6 py-10 text-center text-slate-500">No counselors yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($counselors->hasPages())
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
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
