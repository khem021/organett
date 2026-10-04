@extends('layouts.app')

@section('title', 'Platform Security')
@section('page-title', 'Platform Security')

@section('content')

@php
    $anyOff = in_array(false, $states, true);
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h2>Platform Security</h2>
        <p>Switch ORGANETT's built-in protections on or off for every farm on this platform</p>
    </div>
</div>

@if(session('status'))
    <div style="margin-bottom:1.25rem;padding:.75rem 1rem;background:#14532d33;border:1px solid #16a34a55;border-radius:.5rem;font-size:.875rem;color:var(--green-light);">
        {{ session('status') }}
    </div>
@endif

@if($anyOff)
    <div role="alert" style="margin-bottom:1.25rem;padding:1rem 1.25rem;background:#7f1d1d1a;border:1px solid #dc262688;border-radius:.5rem;">
        <div style="display:flex;align-items:center;gap:.625rem;font-size:.9rem;font-weight:700;color:var(--danger);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            A protection is switched off right now
        </div>
        <p style="margin:.5rem 0 0;font-size:.8125rem;color:var(--text-muted);line-height:1.55;">
            This applies to every farm and to anyone visiting the site, not just your own account.
            Switch it back on as soon as you are done.
        </p>
    </div>
@endif

<div class="card" style="max-width:720px;padding:1.75rem;">
    <div class="card-title">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Built-in Protections
    </div>

    @foreach($toggles as $key => $meta)
        @php
            $on = $states[$key];
            $record = $history[$key] ?? null;
        @endphp
        <div style="padding:1.125rem 0;border-bottom:1px solid var(--card-border);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1.25rem;">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:.625rem;">
                        <span style="font-size:.9rem;font-weight:600;color:var(--text);">{{ $meta['label'] }}</span>
                        <span style="font-size:.6875rem;font-weight:700;padding:.15rem .5rem;border-radius:999px;background:{{ $on ? 'var(--green-soft)' : '#7f1d1d55' }};color:{{ $on ? 'var(--green-light)' : 'var(--danger)' }};">
                            {{ $on ? 'ACTIVE' : 'OFF' }}
                        </span>
                    </div>
                    <div style="font-size:.775rem;color:var(--text-muted);margin-top:.375rem;line-height:1.5;">{{ $meta['covers'] }}</div>
                    <div style="font-size:.775rem;color:{{ $on ? 'var(--text-dim)' : 'var(--danger)' }};margin-top:.3rem;line-height:1.5;">
                        <strong>With this off:</strong> {{ $meta['risk'] }}
                    </div>
                    @if($record && $record->updated_at)
                        <div style="font-size:.7rem;color:var(--text-dim);margin-top:.45rem;">
                            Last changed {{ $record->updated_at->diffForHumans() }}
                            @if($record->updatedBy) by {{ $record->updatedBy->full_name }} @endif
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('admin.security.update') }}" style="flex-shrink:0;"
                      @if($on) data-confirm="Turn OFF {{ $meta['label'] }} for every farm on this platform? This takes effect immediately for all visitors." data-confirm-title="Turn off protection" data-confirm-button="Turn off" @endif>
                    @csrf @method('PATCH')
                    <input type="hidden" name="key" value="{{ $key }}">
                    <input type="hidden" name="enabled" value="{{ $on ? 0 : 1 }}">
                    @if($on)
                        <button type="submit" style="padding:.5rem 1rem;background:transparent;color:var(--danger);border:1px solid #dc262688;border-radius:.5rem;font-size:.8125rem;font-weight:600;cursor:pointer;white-space:nowrap;">
                            Turn off
                        </button>
                    @else
                        <button type="submit" style="padding:.5rem 1rem;background:linear-gradient(135deg,var(--green-mid),var(--green-accent));color:#fff;border:none;border-radius:.5rem;font-size:.8125rem;font-weight:600;cursor:pointer;white-space:nowrap;">
                            Turn back on
                        </button>
                    @endif
                </form>
            </div>
        </div>
    @endforeach

    <div style="display:flex;align-items:center;gap:.75rem;margin-top:1.5rem;">
        <form method="POST" action="{{ route('admin.security.update') }}"
              data-confirm="Turn OFF every protection listed on this page? ORGANETT will stop sending security headers and stop limiting login attempts, for all farms, immediately." data-confirm-title="Turn off the security system" data-confirm-button="Turn everything off">
            @csrf @method('PATCH')
            <input type="hidden" name="key" value="all">
            <input type="hidden" name="enabled" value="0">
            <button type="submit" style="padding:.625rem 1.25rem;background:#dc2626;color:#fff;border:none;border-radius:.5rem;font-size:.875rem;font-weight:600;cursor:pointer;">
                Turn off security system
            </button>
        </form>

        <form method="POST" action="{{ route('admin.security.update') }}">
            @csrf @method('PATCH')
            <input type="hidden" name="key" value="all">
            <input type="hidden" name="enabled" value="1">
            <button type="submit" style="padding:.625rem 1.25rem;background:transparent;color:var(--text-muted);border:1px solid var(--card-border);border-radius:.5rem;font-size:.875rem;font-weight:600;cursor:pointer;">
                Restore all
            </button>
        </form>
    </div>

    <p style="margin:1.25rem 0 0;font-size:.7rem;color:var(--text-dim);line-height:1.6;">
        Every change here is written to the activity log with your name and IP address.
        Two protections are not switchable from this page and stay on: per-farm data isolation,
        and the cap on how many farms one network can register per hour.
    </p>
</div>
@endsection
