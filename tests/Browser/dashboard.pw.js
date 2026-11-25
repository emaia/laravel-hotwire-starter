import { expect, test } from '@playwright/test';
import { t } from './i18n.js';

const email = process.env.PLAYWRIGHT_TEST_EMAIL;
const password = process.env.PLAYWRIGHT_TEST_PASSWORD;

test.skip(!email || !password, 'Dashboard checks require explicit test account credentials.');

async function logIn(page, testInfo) {
    await page.goto('/login');
    // A {project} template separates the authentication state of each viewport.
    await page
        .getByRole('textbox', { name: t('Email'), exact: true })
        .fill(email.replace('{project}', testInfo.project.name));
    await page.getByLabel(t('Password'), { exact: true }).fill(password);
    await page.getByRole('button', { name: t('Log in'), exact: true }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}

test('the desktop sidebar preserves its collapsed state', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'desktop', 'Desktop collapse behavior.');
    await logIn(page, testInfo);
    const sidebar = page.locator('[data-slot="sidebar-wrapper"]');
    const navigation = page.getByRole('navigation', { name: t('Main navigation') });

    await expect(navigation.getByRole('link', { name: t('Dashboard'), exact: true })).toHaveAttribute(
        'aria-current',
        'page',
    );
    await page.getByRole('button', { name: t('Toggle sidebar'), exact: true }).click();
    await expect(sidebar).toHaveAttribute('data-state', 'collapsed');
    await page.reload();
    await expect(sidebar).toHaveAttribute('data-state', 'collapsed');
    await page.getByRole('button', { name: t('Toggle sidebar'), exact: true }).click();
    await expect(sidebar).toHaveAttribute('data-state', 'expanded');
});

test('the mobile sidebar opens as a drawer and restores focus on dismissal', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'mobile', 'Mobile drawer behavior.');
    await logIn(page, testInfo);
    const navigation = page.getByRole('navigation', { name: t('Main navigation') });
    const trigger = page.getByRole('button', { name: t('Toggle sidebar'), exact: true });

    await expect(navigation).toBeHidden();
    await trigger.click();
    await expect(navigation).toBeVisible();
    await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    await page.keyboard.press('Escape');
    await expect(navigation).toBeHidden();
    await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    await expect(trigger).toBeFocused();
});

test('the account menu provides password settings and a working logout', async ({ page }, testInfo) => {
    await logIn(page, testInfo);
    if (testInfo.project.name === 'mobile') {
        await page.getByRole('button', { name: t('Toggle sidebar'), exact: true }).click();
    }
    const trigger = page.getByRole('button', { name: t('Account menu'), exact: true });
    await trigger.click();
    const menu = page.locator(`[id="${await trigger.getAttribute('aria-controls')}"]`);
    await expect(menu.getByRole('link', { name: t('Account security'), exact: true })).toBeVisible();
    await menu.getByRole('link', { name: t('Change password'), exact: true }).click();
    await expect(page).toHaveURL(/\/account\/password$/);

    await page.goto('/dashboard');
    if (testInfo.project.name === 'mobile') {
        await page.getByRole('button', { name: t('Toggle sidebar'), exact: true }).click();
    }
    await trigger.click();
    await page.getByRole('button', { name: t('Log out'), exact: true }).click();
    await expect(page).toHaveURL(/\/$/);
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login$/);
});
