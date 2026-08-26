@extends('layouts.app')
@section('title', $company->company_name)
@section('content')
    <h2>{{ $company->company_name }}</h2>
    <dl>
        <dt>Address</dt><dd>{{ $company->company_address }}</dd>
        <dt>Telephone</dt><dd>{{ $company->company_telephone }}</dd>
        <dt>Email</dt><dd>{{ $company->company_email }}</dd>
        <dt>Owner</dt><dd>{{ $company->owner_name }} — {{ $company->owner_mobile }} — {{ $company->owner_email }}</dd>
        <dt>Contact</dt><dd>{{ $company->contact_name }} — {{ $company->contact_mobile }} — {{ $company->contact_email }}</dd>
        <dt>Status</dt><dd>{{ $company->deactivated ? 'Deactivated' : 'Active' }}</dd>
    </dl>

    <a href="/companies/{{ $company->id }}/edit">Edit company</a>

    @if ($company->deactivated)
        <form method="post" action="/companies/{{ $company->id }}/reactivate">
            @csrf
            <button type="submit">Reactivate company</button>
        </form>
    @else
        <form method="post" action="/companies/{{ $company->id }}/deactivate">
            @csrf
            <button type="submit">Deactivate company</button>
        </form>
    @endif

    <h3>Products</h3>
    <ul>
        @foreach ($products as $product)
            <li>
                <a href="/products/{{ $product->gtin }}">{{ $product->name_en }}</a>
                <span>{{ $product->gtin }}</span>
                @if ($product->is_hidden)<span>Hidden</span>@endif
            </li>
        @endforeach
    </ul>
    @if ($products->isEmpty())
        <p>No products for this company.</p>
    @endif
@endsection
