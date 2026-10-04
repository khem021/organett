@extends('errors.layout')

@section('code', '500')
@section('title', 'Something went wrong on our side')
@section('message', 'The problem has been recorded. Please try again in a moment. If it keeps happening, contact Organett support and tell them what you were doing.')
@section('icon')
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
@endsection
