<?php

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

return static function (Request $request): View {
    $input = $request->validate([
        'search' => ['nullable', 'string', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
        'filters_layout' => ['nullable', 'in:sidebar,toolbar'],
        'pagination_layout' => ['nullable', 'in:stacked,split'],
    ]);
    $search = $input['search'] ?? '';
    $records = collect(range(1, 28))->map(fn (int $number): string => sprintf('Item %02d', $number))
        ->filter(fn (string $name): bool => $search === '' || Str::contains($name, $search, ignoreCase: true))->values();
    $page = (int) ($input['page'] ?? 1);

    return view('fixtures::listing', [
        'search' => $search,
        'filtersLayout' => $input['filters_layout'] ?? 'sidebar',
        'paginationLayout' => $input['pagination_layout'] ?? 'stacked',
        'records' => new LengthAwarePaginator($records->forPage($page, 5)->values(), $records->count(), 5, $page, [
            'path' => url('/_test/listings'),
        ]),
    ]);
};
