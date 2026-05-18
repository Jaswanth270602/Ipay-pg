@extends('layouts.app-sidebar')

@section('title', 'Reports - Admin - ' . config('app.name'))
@section('page-title', 'Reports')

@section('content')
@include('admin.reports.hub')
@endsection

