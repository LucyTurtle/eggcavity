@extends('layouts.app')

@section('title', 'User manager')

@section('content')
<div class="page-header">
    <h1>User manager</h1>
    <p class="lead">Add, edit, ban, or reset passwords. Developers only.</p>
</div>

<style>
    .content-table { width: 100%; border-collapse: collapse; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
    .content-table th, .content-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border); }
    .content-table th { background: var(--bg); font-weight: 600; font-size: 0.875rem; color: var(--text-secondary); }
    .content-table tr:last-child td { border-bottom: none; }
    .content-table a { color: var(--accent); font-weight: 500; }
    .content-table a:hover { text-decoration: underline; }
    .content-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .content-actions form { margin: 0; }
    .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8125rem; border-radius: var(--radius-sm); cursor: pointer; font-family: inherit; text-decoration: none; display: inline-block; border: 1px solid var(--border); background: var(--surface); color: var(--text); }
    .btn-sm:hover { border-color: var(--accent); color: var(--accent); }
    .btn-sm.btn-danger { border-color: #dc2626; color: #dc2626; }
    .btn-sm.btn-danger:hover { background: #fef2f2; }
    .btn-sm.btn-success { border-color: #16a34a; color: #16a34a; }
    .btn-sm.btn-success:hover { background: #f0fdf4; }
    .btn-add { padding: 0.35rem 0.75rem; font-size: 0.875rem; background: var(--accent); color: white; border: none; border-radius: var(--radius-sm); font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-add:hover { background: var(--accent-hover); }
    .badge { display: inline-block; padding: 0.15rem 0.5rem; font-size: 0.75rem; border-radius: var(--radius-sm); font-weight: 500; }
    .badge-banned { background: #fef2f2; color: #dc2626; }
    .content-pagination { margin-top: 1.5rem; }
    .content-pagination nav { display: flex; justify-content: center; flex-wrap: wrap; gap: 0.25rem; }
    .content-pagination ul.pagination { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 0.25rem; justify-content: center; }
    .content-pagination ul.pagination li { display: inline-block; }
    .content-pagination ul.pagination a, .content-pagination ul.pagination span { padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.875rem; text-decoration: none; border: 1px solid var(--border); background: var(--surface); color: var(--text); display: inline-block; }
    .content-pagination ul.pagination a:hover { background: var(--accent-muted); border-color: var(--accent); color: var(--accent); }
    .content-pagination ul.pagination span { background: var(--bg); color: var(--text-secondary); }
    .content-pagination ul.pagination li.disabled span { cursor: not-allowed; }
    .content-pagination ul.pagination li.active span { background: var(--accent-muted); border-color: var(--accent); color: var(--accent); }
</style>

@if(session('success'))
    <div class="card" style="background: color-mix(in srgb, var(--accent) 12%, transparent); border-color: var(--accent); margin-bottom: 1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="card" style="background: #fef2f2; border-color: #dc2626; margin-bottom: 1rem;">{{ session('error') }}</div>
@endif

<div style="margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; align-items: center; gap: 1rem;">
    <a href="{{ route('users.create') }}" class="btn-add">Add user</a>
    <span style="font-size: 0.9375rem; color: var(--text-secondary);">Page {{ $users->currentPage() }} of {{ $users->lastPage() }} ({{ $users->total() }} total)</span>
</div>

@if($users->isEmpty())
    <p style="color: var(--text-secondary); font-size: 0.9375rem;">No users.</p>
@else
    <table class="content-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th style="width: 14rem;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $u)
                <tr>
                    <td><a href="{{ route('users.edit', $u) }}">{{ $u->name }}</a></td>
                    <td>{{ $u->email }}</td>
                    <td><code style="font-size: 0.8125rem;">{{ $u->role }}</code></td>
                    <td>
                        @if($u->isBanned())
                            <span class="badge badge-banned">Banned</span>
                        @else
                            <span style="color: var(--text-secondary); font-size: 0.875rem;">Active</span>
                        @endif
                    </td>
                    <td>
                        <div class="content-actions">
                            <a href="{{ route('users.edit', $u) }}" class="btn-sm">Edit</a>
                            @if(auth()->id() !== $u->id)
                                @if($u->isBanned())
                                    <form method="post" action="{{ route('users.unban', $u) }}">
                                        @csrf
                                        <button type="submit" class="btn-sm btn-success">Unban</button>
                                    </form>
                                @else
                                    <form method="post" action="{{ route('users.ban', $u) }}" onsubmit="return confirm('Ban this user? They will not be able to log in.');">
                                        @csrf
                                        <button type="submit" class="btn-sm btn-danger">Ban</button>
                                    </form>
                                @endif
                            @endif
                            @if(auth()->user()->isDeveloper())
                                <form method="post" action="{{ route('impersonate.start', $u) }}">
                                    @csrf
                                    <button type="submit" class="btn-sm">View as</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($users->hasPages())
        <div class="content-pagination">
            {{ $users->links('pagination::custom') }}
        </div>
    @endif
@endif

<p style="margin-top: 1.5rem;"><a href="{{ route('dashboard') }}">← Back to dashboard</a></p>
@endsection
