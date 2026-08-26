@extends('layouts.app')
@section('title', 'Admin login')
@section('content')
    <h2>Admin login</h2>
    @if ($errors->any())
        <p role="alert">{{ $errors->first() }}</p>
    @endif
    <form method="post" action="/login">
        @csrf
        <label for="passphrase">Passphrase</label>
        <input id="passphrase" name="passphrase" type="password" autocomplete="current-password">
        <button type="submit">Sign in</button>
    </form>
@endsection
