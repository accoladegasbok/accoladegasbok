{{-- FILE: resources/views/admin/training/not-ready.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Training')
@section('page-title', 'Staff Training')
@section('content')
<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl px-4 py-3 text-sm font-body">Training is not set up yet. Ask the admin to run the database update (php artisan migrate --force).</div>
@endsection
