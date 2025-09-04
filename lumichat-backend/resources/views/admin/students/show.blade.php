@extends('layouts.admin')

@section('title', 'Student Details')

@section('content')
<div class="max-w-5xl mx-auto p-6 space-y-6">
  
  {{-- Header --}}
  <div class="flex items-center justify-between">
    <h2 class="text-2xl font-semibold">Student Details</h2>
    <a href="{{ route('admin.students.index') }}" 
       class="text-sm text-indigo-600 hover:underline">← Back to list</a>
  </div>

  {{-- Card --}}
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-8 space-y-6 border">
    
    {{-- Info Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <p class="text-sm text-gray-500">FULL NAME</p>
        <p class="text-lg font-medium">{{ $student->full_name }}</p>
      </div>
      <div>
        <p class="text-sm text-gray-500">EMAIL</p>
        <p class="text-lg font-medium">{{ $student->email }}</p>
      </div>

      <div>
        <p class="text-sm text-gray-500">CONTACT NUMBER</p>
        <p class="text-lg font-medium">{{ $student->contact_number }}</p>
      </div>
      <div>
        <p class="text-sm text-gray-500">COURSE</p>
        <p class="text-lg font-medium">{{ $student->course }}</p>
      </div>

      <div>
        <p class="text-sm text-gray-500">YEAR LEVEL</p>
        <p class="text-lg font-medium">{{ $student->year_level }}</p>
      </div>
    </div>

    {{-- Dates --}}
    <div class="border-t pt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <p class="text-sm text-gray-500">CREATED</p>
        <p class="text-lg font-medium">{{ $student->created_at->format('F d, Y • h:i A') }}</p>
      </div>
      <div>
        <p class="text-sm text-gray-500">UPDATED</p>
        <p class="text-lg font-medium">{{ $student->updated_at->format('F d, Y • h:i A') }}</p>
      </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-4 pt-4">
      <a href="mailto:{{ $student->email }}" 
         class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
        Email Student
      </a>
      <button onclick="navigator.clipboard.writeText('{{ $student->email }}')" 
              class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
        Copy Email
      </button>
    </div>
  </div>
</div>
@endsection
