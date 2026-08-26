@extends('layouts.app')
@section('title', 'Companies')
@section('content')
    <h2>Companies</h2>
    <a href="/companies/new">New company</a>
    <ul>
        @foreach ($companies as $company)
            <li>
                <a href="/companies/{{ $company->id }}">{{ $company->company_name }}</a>
                <span>{{ $company->company_email }}</span>
            </li>
        @endforeach
    </ul>
@endsection
