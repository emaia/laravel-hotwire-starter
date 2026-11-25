<?php

use Dom\HTMLDocument;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

beforeEach(function (): void {
    app()->setLocale('pt_BR');
    View::addNamespace('fixtures', base_path('tests/Fixtures'));
    Route::get('/_test/listings', require base_path('tests/Fixtures/listing-route.php'))->middleware('web');
});

test('listing filters support sidebar and toolbar layouts', function (string $layout): void {
    $this->get('/_test/listings?filters_layout='.$layout)->assertOk()
        ->assertSee('Busca e filtros')->assertSee('Aplicar filtros')->assertSee('Limpar filtros')
        ->assertSee('method="get"', false)->assertSee('Item 01')->assertSee('Item 05');
})->with(['sidebar', 'toolbar']);

test('pagination preserves the search and display options in its links', function (): void {
    $this->get('/_test/listings?search=Item+1&page=2&filters_layout=toolbar&pagination_layout=split')
        ->assertOk()->assertSee('6–10 de 10 resultados')
        ->assertSee('Item 15')->assertSee('Item 19')->assertDontSee('Item 10')
        ->assertSee('search=Item%201', false)
        ->assertSee('filters_layout=toolbar', false)
        ->assertSee('pagination_layout=split', false)
        ->assertSee('data-turbo-frame="listing-results"', false)
        ->assertSee('aria-label="Página anterior"', false)
        ->assertSee('aria-label="Próxima página"', false);
});

test('applying filters without a page parameter starts from the first page', function (): void {
    $this->get('/_test/listings?search=Item+1')->assertOk()
        ->assertSee('Item 10')->assertSee('Item 14')->assertDontSee('Item 15')
        ->assertDontSee('name="page"', false);
});

test('empty search results do not show pagination or a misleading counter', function (): void {
    $this->get('/_test/listings?search=missing')->assertOk()
        ->assertSee('Nenhum resultado encontrado.')
        ->assertDontSee('data-listing-pagination', false);
});

test('a single page does not render redundant pagination', function (): void {
    $this->get('/_test/listings?search=Item+28')->assertOk()
        ->assertSee('Item 28')->assertDontSee('data-listing-pagination', false);
});

test('filters escape rejected markup in the search control', function (): void {
    $search = '"><script>alert(1)</script>';

    $response = $this->get('/_test/listings?'.http_build_query(['search' => $search]));
    $response->assertOk()->assertDontSee($search, false);
    $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);

    expect($document->querySelector('input[name="search"]')->getAttribute('value'))->toBe($search);
});

test('pagination does not mutate the caller paginator and supports a custom page name', function (): void {
    $paginator = new LengthAwarePaginator(['A', 'B'], 6, 2, 2, [
        'path' => '/_test/listings', 'pageName' => 'results_page',
    ]);

    $this->blade('<x-listing-pagination :paginator="$paginator" layout="split" />', compact('paginator'))
        ->assertSee('results_page=1', false)->assertSee('results_page=3', false);

    expect($paginator->onEachSide)->toBe(3);
});

test('listing components remain usable in English', function (): void {
    app()->setLocale('en');

    $this->get('/_test/listings')->assertOk()
        ->assertSee('Search and filters')->assertSee('Apply filters')->assertSee('1–5 of 28 results');
});

test('a listing without filters keeps the content and caller attributes', function (): void {
    $this->blade('<x-listing-layout id="records" class="custom-list">Results</x-listing-layout>')
        ->assertSee('id="records"', false)->assertSee('custom-list')->assertSee('Results')
        ->assertDontSee('data-listing-filters', false);
});

test('sidebar filters can start collapsed with a custom heading', function (): void {
    $view = $this->blade('<x-listing-layout :filters-open="false" filters-label="Custom filters">
        <x-slot:filters>Filter controls</x-slot:filters>
        Results
    </x-listing-layout>');
    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    $view->assertSee('Custom filters')->assertSee('Filter controls')->assertSee('Results');
    expect($document->querySelector('details')->hasAttribute('open'))->toBeFalse();
});
