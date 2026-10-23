@extends('layouts.app')
@section('title', 'Listings')

@section('content')
<div class="page-head">
    <h1>Nearby listings</h1>
    <p class="muted">Curated hotels, lodges, food and essentials. Provenance is mandatory: record where each listing came from. Ratings are provider opinions, never safety assessments.</p>
</div>

@include('admin._nav')

<section class="card">
    <h2 class="mt-0">Add listing</h2>
    <form method="POST" action="{{ route('admin.places.store') }}">
        @csrf
        <div class="row">
            <div>
                <label for="p-name">Name <span class="req">*</span></label>
                <input id="p-name" name="name" type="text" required maxlength="200" value="{{ old('name') }}">
            </div>
            <div>
                <label for="p-category">Category <span class="req">*</span></label>
                <select id="p-category" name="category" required>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="p-city">City <span class="req">*</span></label>
                <input id="p-city" name="city" type="text" required maxlength="100" value="{{ old('city') }}">
            </div>
            <div>
                <label for="p-centre">Near centre</label>
                <select id="p-centre" name="exam_centre_id">
                    <option value="">—</option>
                    @foreach($centres as $centre)
                        <option value="{{ $centre->id }}" @selected(old('exam_centre_id') == $centre->id)>{{ $centre->city }} — {{ $centre->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3">
            <label for="p-address">Address</label>
            <input id="p-address" name="address" type="text" maxlength="500" value="{{ old('address') }}">
        </div>

        <div class="row mt-3">
            <div><label for="p-phone">Phone</label><input id="p-phone" name="phone" type="text" maxlength="30" value="{{ old('phone') }}"></div>
            <div><label for="p-website">Website</label><input id="p-website" name="website" type="url" maxlength="500" value="{{ old('website') }}"></div>
            <div><label for="p-hours">Opening hours</label><input id="p-hours" name="opening_hours" type="text" maxlength="200" value="{{ old('opening_hours') }}"></div>
        </div>

        <div class="row mt-3">
            <div><label for="p-rating">Rating (0–5)</label><input id="p-rating" name="rating" type="text" value="{{ old('rating') }}"></div>
            <div><label for="p-reviews">Reviews count</label><input id="p-reviews" name="reviews_count" type="number" min="0" value="{{ old('reviews_count') }}"></div>
            <div><label for="p-price">Price from (₹)</label><input id="p-price" name="price_from" type="number" min="0" value="{{ old('price_from') }}"></div>
            <div><label for="p-lat">Latitude</label><input id="p-lat" name="latitude" type="text" value="{{ old('latitude') }}"></div>
            <div><label for="p-lng">Longitude</label><input id="p-lng" name="longitude" type="text" value="{{ old('longitude') }}"></div>
        </div>

        <div class="row mt-3">
            <div>
                <label for="p-source">Source name <span class="req">*</span></label>
                <input id="p-source" name="source_name" type="text" required maxlength="200" value="{{ old('source_name') }}" placeholder="e.g. Verified by phone call on 12 Oct 2026">
                @error('source_name')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div>
                <label for="p-source-url">Source URL</label>
                <input id="p-source-url" name="source_url" type="url" maxlength="500" value="{{ old('source_url') }}">
            </div>
            <div>
                <label for="p-status">Data status <span class="req">*</span></label>
                <select id="p-status" name="data_status" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(old('data_status') === $status)>{{ \App\Integrations\Support\DataStatus::label($status) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3">
            <label for="p-limitation">Limitation note</label>
            <input id="p-limitation" name="error_or_limitation" type="text" maxlength="500" value="{{ old('error_or_limitation') }}">
        </div>

        <div class="card-actions">
            <button class="btn mt-3" type="submit">Add listing</button>
        </div>
    </form>
</section>

<section class="section">
    <h2>Existing listings</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th scope="col">Name</th><th scope="col">Category</th><th scope="col">City</th><th scope="col">Centre</th><th scope="col">Status</th><th scope="col">Source</th><th scope="col"></th></tr></thead>
            <tbody>
                @forelse($places as $place)
                    <tr>
                        <td>{{ $place->name }}@if($place->is_demo)<br><span class="badge b-demo">demo</span>@endif</td>
                        <td class="small">{{ $categories[$place->category] ?? $place->category }}</td>
                        <td class="small">{{ $place->city }}</td>
                        <td class="small">{{ $place->centre?->name ?? '—' }}</td>
                        <td><span class="badge b-{{ $place->data_status }}">{{ \App\Integrations\Support\DataStatus::label($place->data_status) }}</span></td>
                        <td class="small muted">{{ $place->source_name }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.places.destroy', $place) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn sm danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No listings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager">{{ $places->links() }}</div>
</section>
@endsection
