@extends('layouts.app')
@section('title', 'Deactivated companies')
@section('content')
    <h2>Deactivated companies</h2>
    <ul>
        @foreach ($companies as $company)
            <li><a href="/companies/{{ $company->id }}">{{ $company->company_name }}</a></li>
        @endforeach
    </ul>
    @if ($companies->isEmpty())
        <p>No deactivated companies.</p>
    @endif
@endsection
