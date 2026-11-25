import { expect, test } from '@playwright/test';
import { t } from './i18n.js';

test.skip(process.env.PLAYWRIGHT_LISTING_FIXTURE !== '1', 'Listing checks require the isolated fixture server.');

test('filter submission resets the page and pagination preserves the filter', async ({ page }) => {
    await page.goto('/_test/listings?search=Item+1&page=2');
    await page.getByRole('textbox', { name: t('Search'), exact: true }).fill('Item 2');
    await page.getByRole('button', { name: t('Apply filters'), exact: true }).click();
    await expect(page.getByRole('region', { name: t('Results') })).toContainText('Item 20');
    expect(new URL(page.url()).searchParams.get('page')).toBeNull();

    await page.getByRole('link', { name: t('Next page'), exact: true }).click();
    await expect(page.getByRole('region', { name: t('Results') })).toContainText('Item 25');
    expect(new URL(page.url()).searchParams.get('search')).toBe('Item 2');
    expect(new URL(page.url()).searchParams.get('page')).toBe('2');
});

test('clearing filters removes the query and disables the previous-page control', async ({ page }) => {
    await page.goto('/_test/listings?search=Item+1&page=2');
    await page.getByRole('link', { name: t('Clear filters'), exact: true }).click();

    await expect(page.getByRole('region', { name: t('Results') })).toContainText('Item 01');
    await expect(page.getByRole('textbox', { name: t('Search'), exact: true })).toHaveValue('');
    expect(new URL(page.url()).search).toBe('');
    await expect(page.getByRole('link', { name: t('Previous page'), exact: true })).toBeDisabled();
});

test('sidebar filters can be closed and reopened', async ({ page }) => {
    await page.goto('/_test/listings');
    const disclosure = page.getByText(t('Search and filters'), { exact: true });
    const search = page.getByRole('textbox', { name: t('Search'), exact: true });

    await disclosure.click();
    await expect(search).toBeHidden();
    await disclosure.click();
    await expect(search).toBeVisible();
});
