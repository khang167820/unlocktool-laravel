@extends('admin.layouts.app')
@section('title', 'Edit Account')
@section('page-title', 'Edit Account #' . $account->id)

@section('content')
<div class="admin-card" style="max-width: 600px;">
    <form action="{{ route('admin.accounts.update', $account->id) }}" method="POST">
        @csrf
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-input" value="{{ $account->username }}">
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="text" name="password" class="form-input" value="{{ $account->password }}">
        </div>
        <div class="form-group">
            <label class="form-label">Note</label>
            <input type="text" name="note" class="form-input" value="{{ $account->note }}">
        </div>
        <div class="form-group">
            <label class="form-label">Note Date</label>
            <input type="date" name="note_date" class="form-input" value="{{ $account->note_date }}">
        </div>
        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('admin.accounts') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
