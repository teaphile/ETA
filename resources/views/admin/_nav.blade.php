@php $isFullAdmin = auth()->user()?->isAdmin() ?? false; @endphp
<nav class="chiprow mb-5" aria-label="Admin sections">
    <a class="chip" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>Dashboard</a>
    <a class="chip" href="{{ route('admin.health') }}" @if(request()->routeIs('admin.health')) aria-current="page" @endif>Integration health</a>
    <a class="chip" href="{{ route('admin.reports') }}" @if(request()->routeIs('admin.reports')) aria-current="page" @endif>Reports</a>
    <a class="chip" href="{{ route('admin.stale') }}" @if(request()->routeIs('admin.stale')) aria-current="page" @endif>Stale data</a>
    @if($isFullAdmin)
        <a class="chip" href="{{ route('admin.exams') }}" @if(request()->routeIs('admin.exams')) aria-current="page" @endif>Examinations</a>
        <a class="chip" href="{{ route('admin.centres') }}" @if(request()->routeIs('admin.centres')) aria-current="page" @endif>Centres</a>
        <a class="chip" href="{{ route('admin.import') }}" @if(request()->routeIs('admin.import*')) aria-current="page" @endif>CSV import</a>
        <a class="chip" href="{{ route('admin.users') }}" @if(request()->routeIs('admin.users')) aria-current="page" @endif>Users</a>
        <a class="chip" href="{{ route('admin.faqs') }}" @if(request()->routeIs('admin.faqs')) aria-current="page" @endif>FAQs</a>
        <a class="chip" href="{{ route('admin.places') }}" @if(request()->routeIs('admin.places')) aria-current="page" @endif>Listings</a>
        <a class="chip" href="{{ route('admin.audits') }}" @if(request()->routeIs('admin.audits')) aria-current="page" @endif>Audit trail</a>
    @endif
</nav>
