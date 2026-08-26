@extends('layouts.app')
@section('title', $product->exists ? 'Edit product' : 'New product')
@section('content')
    <h2>{{ $product->exists ? 'Edit product' : 'New product' }}</h2>

    @if ($errors->any())
        <ul role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="post" action="{{ $product->exists ? '/products/'.$product->gtin : '/products' }}" enctype="multipart/form-data">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div>
            <label for="company_id">Company</label>
            <select id="company_id" name="company_id">
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected(old('company_id', $product->company_id) == $company->id)>
                        {{ $company->company_name }}
                    </option>
                @endforeach
            </select>
        </div>

        @foreach ([
            'gtin' => 'GTIN (13 or 14 digits)',
            'name_en' => 'Name (English)',
            'name_fr' => 'Name (French)',
            'brand' => 'Brand',
            'country_of_origin' => 'Country of origin',
            'weight_gross' => 'Gross weight',
            'weight_net' => 'Net content weight',
            'weight_unit' => 'Weight unit',
        ] as $field => $label)
            <div>
                <label for="{{ $field }}">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $product->{$field}) }}">
            </div>
        @endforeach

        <div>
            <label for="description_en">Description (English)</label>
            <textarea id="description_en" name="description_en">{{ old('description_en', $product->description_en) }}</textarea>
        </div>
        <div>
            <label for="description_fr">Description (French)</label>
            <textarea id="description_fr" name="description_fr">{{ old('description_fr', $product->description_fr) }}</textarea>
        </div>
        <div>
            <label for="image">Product image</label>
            <input id="image" name="image" type="file" accept="image/*">
        </div>

        <button type="submit">Save product</button>
    </form>
@endsection
