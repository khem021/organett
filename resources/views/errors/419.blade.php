@extends('errors.layout')

@section('code', '419')
@section('title', 'Your session timed out')
@section('message', 'For your security, this page expired before it was sent, so nothing was saved. Go back, reload the page and try again. If you were signed out, sign in first.')
@section('icon')
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ url()->previous(url('/login')) }}">&larr; Go back and try again</a>
    <a class="btn btn-secondary" href="{{ url('/login') }}">Sign in</a>
@endsection
