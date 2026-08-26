@extends('layouts.app')
@section('title', 'GTIN verification')
@section('content')
    <h2>GTIN bulk verification</h2>

    <form method="post" action="/verify">
        @csrf
        <label for="gtins">GTIN codes, one per line</label>
        <textarea id="gtins" name="gtins" rows="8">{{ $input }}</textarea>
        <button type="submit">Verify</button>
    </form>

    @isset($results)
        @if (($allValid ?? false))
            <p class="all-valid" role="status"><span aria-hidden="true">&check;</span> All valid</p>
        @endif

        <ul class="verification-results">
            @foreach ($results as $result)
                <li data-gtin="{{ $result['gtin'] }}">
                    <span>{{ $result['gtin'] }}</span>
                    <span>{{ $result['valid'] ? 'Valid' : 'Not valid' }}</span>
                </li>
            @endforeach
        </ul>

        @if ($results === [])
            <p>No GTIN codes submitted.</p>
        @endif
    @endisset
@endsection
