@extends('layouts.app')
@section('title', $company->exists ? 'Edit company' : 'New company')
@section('content')
    <h2>{{ $company->exists ? 'Edit company' : 'New company' }}</h2>

    @if ($errors->any())
        <ul role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="post" action="{{ $company->exists ? '/companies/'.$company->id : '/companies' }}">
        @csrf
        @if ($company->exists)
            @method('PUT')
        @endif
        @foreach ([
            'company_name' => 'Company name',
            'company_address' => 'Company address',
            'company_telephone' => 'Company telephone',
            'company_email' => 'Company email',
            'owner_name' => 'Owner name',
            'owner_mobile' => 'Owner mobile number',
            'owner_email' => 'Owner email',
            'contact_name' => 'Contact name',
            'contact_mobile' => 'Contact mobile number',
            'contact_email' => 'Contact email',
        ] as $field => $label)
            <div>
                <label for="{{ $field }}">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $company->{$field}) }}">
            </div>
        @endforeach
        <button type="submit">Save company</button>
    </form>
@endsection
