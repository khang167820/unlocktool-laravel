@extends('admin.layouts.app')
@section('title', 'Price Management')
@section('page-title', 'Price Management')

@section('content')
<!-- Add/Edit Price -->
<div class="admin-card">
    <div class="admin-card-title">➕ Add New Price Package</div>
    <form action="{{ route('admin.prices.save') }}" method="POST" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
        @csrf
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Hours</label>
            <input type="number" name="hours" class="form-input" required style="width: 100px;" min="1">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Price (VND)</label>
            <input type="number" name="price" class="form-input" required style="width: 150px;" min="1000">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Original Price</label>
            <input type="number" name="original_price" class="form-input" style="width: 150px;">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Discount %</label>
            <input type="number" name="discount_percent" class="form-input" style="width: 100px;">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Promo Badge</label>
            <input type="text" name="promo_badge" class="form-input" style="width: 120px;" placeholder="e.g. HOT">
        </div>
        <button type="submit" class="btn btn-success">Add Price</button>
    </form>
</div>

<!-- Prices Table -->
<div class="admin-card">
    <div class="admin-card-title">💰 Current Prices</div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Hours</th>
                <th>Price</th>
                <th>Original</th>
                <th>Discount</th>
                <th>Promo</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($prices as $price)
            <tr>
                <td>#{{ $price->id }}</td>
                <td><strong>{{ $price->hours }}h</strong></td>
                <td style="color: #10b981; font-weight: 600;">{{ number_format($price->price, 0, ',', '.') }}đ</td>
                <td>{{ $price->original_price ? number_format($price->original_price, 0, ',', '.') . 'đ' : '—' }}</td>
                <td>{{ $price->discount_percent ? $price->discount_percent . '%' : '—' }}</td>
                <td>{{ $price->promo_badge ?? '—' }}</td>
                <td>
                    <form action="{{ route('admin.prices.delete', $price->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this price?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">🗑 Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #64748b; padding: 40px;">No prices configured</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
