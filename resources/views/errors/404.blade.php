@extends('errors.layout')

@section('code', '404')
@section('title', 'Page not found')
@section('message', 'We could not find that page. It may have been moved or deleted, or the address may be mistyped.')
@section('icon')
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
@endsection
