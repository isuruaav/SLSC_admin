<form method="GET" class="history-year-filter" role="search">
    @if(is_string(request()->query('tab')))<input type="hidden" name="tab" value="{{ request()->query('tab') }}">@endif
    <label for="history-year">{{ $currentLocale === 'si' ? 'වර්ෂය අනුව සොයන්න' : 'Find appointments by year' }}<input id="history-year" type="number" name="year" min="1900" max="9999" value="{{ $selectedYear }}" placeholder="{{ now()->year }}"></label>
    <button type="submit">{{ $currentLocale === 'si' ? 'සොයන්න' : 'Apply' }}</button>
    <label for="history-view">{{ $currentLocale === 'si' ? 'දර්ශනය' : 'View' }}<select id="history-view" name="view"><option value="grid" @selected($historyView === 'grid')>{{ $currentLocale === 'si' ? 'කාඩ්පත්' : 'Cards' }}</option><option value="timeline" @selected($historyView === 'timeline')>{{ $currentLocale === 'si' ? 'කාලරේඛාව' : 'Timeline' }}</option></select></label>
    <a href="{{ request()->fullUrlWithQuery(['year' => null]) }}">{{ $currentLocale === 'si' ? 'සියලු වර්ෂ' : 'All years' }}</a>
</form>
