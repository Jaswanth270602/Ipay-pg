@extends('layouts.app-sidebar')

@section('title', 'Analytics Reports - Admin - ' . config('app.name'))
@section('page-title', 'Analytics reports')

@section('content')
@include('reports.partials.analytics-page', [
    'dataUrl' => route('admin.reports.analytics.data'),
    'exportUrl' => route('admin.reports.analytics.export'),
    'isAdmin' => true,
    'breadcrumbs' => [
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'Analytics'],
    ],
    'reportTypes' => $reportTypes,
])
@endsection
