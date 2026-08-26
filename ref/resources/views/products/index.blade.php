@extends('layouts.app')
@section('title', 'Products')
@section('content')
    <h2>Products</h2>
    <a href="/products/new">New product</a>

    <form method="get" action="/products" role="search">
        <label for="query">Search products</label>
        <input id="query" name="query" type="search" value="{{ $keyword }}">
        <button type="submit">Search</button>
    </form>

    <ul>
        @foreach ($products as $product)
            <li>
                <a href="/products/{{ $product->gtin }}">{{ $product->name_en }}</a>
                <span>{{ $product->gtin }}</span>
                <span>{{ $product->company?->company_name }}</span>
                @if ($product->is_hidden)<span>Hidden</span>@endif
            </li>
        @endforeach
    </ul>
    @if ($products->isEmpty())
        <p>No products match.</p>
    @endif
@endsection
