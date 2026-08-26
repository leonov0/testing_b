@extends('layouts.app')
@section('title', $product->name_en)
@section('content')
    <h2>{!! $product->name_en !!}</h2>
    <p>{{ $product->name_fr }}</p>

    @if ($product->image_path)
        <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name_en }}">
        <form method="post" action="/products/{{ $product->gtin }}/remove-image">
            @csrf
            <button type="submit">Remove image</button>
        </form>
    @else
        <img src="/images/product-placeholder.svg" alt="No product image uploaded">
    @endif

    <dl>
        <dt>GTIN</dt><dd>{{ $product->gtin }}</dd>
        <dt>Brand</dt><dd>{{ $product->brand }}</dd>
        <dt>Country of origin</dt><dd>{{ $product->country_of_origin }}</dd>
        <dt>Gross weight</dt><dd>{{ $product->weight_gross }} {{ $product->weight_unit }}</dd>
        <dt>Net content</dt><dd>{{ $product->weight_net }} {{ $product->weight_unit }}</dd>
        <dt>Company</dt><dd><a href="/companies/{{ $product->company_id }}">{{ $product->company?->company_name }}</a></dd>
        <dt>Status</dt><dd>{{ $product->is_hidden ? 'Hidden' : 'Visible' }}</dd>
    </dl>

    <h3>Description</h3>
    <p>{!! $product->description_en !!}</p>
    <p lang="fr">{{ $product->description_fr }}</p>

    <a href="/products/{{ $product->gtin }}/edit">Edit product</a>

    @if ($product->is_hidden)
        <form method="post" action="/products/{{ $product->gtin }}/unhide">
            @csrf
            <button type="submit">Unhide product</button>
        </form>
        <form method="post" action="/products/{{ $product->gtin }}">
            @csrf
            @method('DELETE')
            <button type="submit">Delete product permanently</button>
        </form>
    @else
        <form method="post" action="/products/{{ $product->gtin }}/hide">
            @csrf
            <button type="submit">Hide product</button>
        </form>
    @endif
@endsection
