@extends('layouts.app')
@section('title', 'FAQs')

@section('content')
<div class="page-head">
    <h1>Help &amp; FAQ articles</h1>
    <p class="muted">Published FAQs are also used to ground the assistant's answers, so keep them factual and reference an official source where possible.</p>
</div>

@include('admin._nav')

<section class="card">
    <h2 class="mt-0">Add FAQ</h2>
    <form method="POST" action="{{ route('admin.faqs.store') }}">
        @csrf
        <div>
            <label for="faq-q">Question <span class="req">*</span></label>
            <input id="faq-q" name="question" type="text" required minlength="6" maxlength="300" value="{{ old('question') }}">
        </div>
        <div class="mt-3">
            <label for="faq-a">Answer <span class="req">*</span></label>
            <textarea id="faq-a" name="answer" required minlength="10" maxlength="5000">{{ old('answer') }}</textarea>
        </div>
        <div class="row mt-3">
            <div>
                <label for="faq-cat">Category <span class="req">*</span></label>
                <input id="faq-cat" name="category" type="text" required maxlength="100" value="{{ old('category', 'General') }}">
            </div>
            <div>
                <label for="faq-url">Official source URL</label>
                <input id="faq-url" name="source_url" type="url" maxlength="500" value="{{ old('source_url') }}">
            </div>
            <div>
                <label for="faq-order">Sort order</label>
                <input id="faq-order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', 0) }}">
            </div>
        </div>
        <div class="checkbox-row mt-3">
            <input type="checkbox" id="faq-published" name="is_published" value="1" @checked(old('is_published', true))>
            <label for="faq-published">Published</label>
        </div>
        <div class="card-actions">
            <button class="btn" type="submit">Save FAQ</button>
        </div>
    </form>
</section>

<section class="section">
    <h2>Published &amp; draft articles</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th scope="col">Question</th><th scope="col">Category</th><th scope="col">Order</th><th scope="col">Published</th><th scope="col"></th></tr></thead>
            <tbody>
                @forelse($faqs as $faq)
                    <tr>
                        <td>{{ $faq->question }}</td>
                        <td class="small">{{ $faq->category }}</td>
                        <td>{{ $faq->sort_order }}</td>
                        <td><span class="badge b-{{ $faq->is_published ? 'officially_verified' : 'stale' }}">{{ $faq->is_published ? 'yes' : 'no' }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn sm danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No FAQs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager">{{ $faqs->links() }}</div>
</section>
@endsection
