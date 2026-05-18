@extends('layouts.app-sidebar')

@section('title', 'Analytics Reports - ' . config('app.name'))
@section('page-title', 'Analytics reports')

@section('content')
@include('reports.partials.analytics-page', [
    'dataUrl' => route('merchant.reports.analytics.data'),
    'exportUrl' => route('merchant.reports.analytics.export'),
    'isAdmin' => false,
    'breadcrumbs' => [
        ['label' => 'Home', 'url' => route('dashboard')],
        ['label' => 'Reports', 'url' => route('merchant.reports.index')],
        ['label' => 'Analytics'],
    ],
    'reportTypes' => $reportTypes,
])
@endsection
