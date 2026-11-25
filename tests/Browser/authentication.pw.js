import { expect, test } from '@playwright/test';
import { t } from './i18n.js';

test('the welcome login link opens the form and focuses the email field', async ({ page }) => {
    await page.goto('/');

    await page.getByRole('link', { name: t('Log in'), exact: true }).click();
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole('textbox', { name: t('Email'), exact: true })).toBeFocused();
});

test('the login form is labelled and links to registration and password recovery', async ({ page }) => {
    await page.goto('/login');

    await expect(page.getByRole('textbox', { name: t('Email'), exact: true })).toHaveAttribute(
        'autocomplete',
        'username',
    );
    await expect(page.getByLabel(t('Password'), { exact: true })).toHaveAttribute('type', 'password');
    await page.getByRole('link', { name: t('Forgot your password?') }).click();
    await expect(page.getByRole('heading', { name: t('Forgot your password?') })).toBeVisible();
    await page.getByRole('link', { name: t('Back to login') }).click();
    await page.getByRole('link', { name: t('Sign up') }).click();
    await expect(page.getByRole('heading', { name: t('Create your account') })).toBeVisible();
    await expect(page.getByLabel(t('Confirm password'))).toBeVisible();
});

test('a theme selected on the welcome page survives reload and login navigation', async ({ page }) => {
    await page.emulateMedia({ colorScheme: 'light' });
    await page.goto('/');
    const toggle = page.getByRole('button', { name: t('Toggle color scheme'), exact: true });

    await toggle.click();
    await toggle.click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
    await page.reload();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');

    await page.getByRole('link', { name: t('Log in'), exact: true }).click();
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
});

test('the login form disables submit only while a request is pending', async ({ page }) => {
    await page.goto('/login');
    const button = page.getByRole('button', { name: t('Log in'), exact: true });
    const html = await page.content();
    let release;
    const pending = new Promise((resolve) => {
        release = resolve;
    });
    await page.route('**/login', async (route) => {
        if (route.request().method() !== 'POST') {
            return route.continue();
        }
        await pending;
        await route.fulfill({ status: 422, contentType: 'text/html', body: html });
    });
    await page.getByRole('textbox', { name: t('Email'), exact: true }).fill('spinner@example.test');
    await page.getByLabel(t('Password'), { exact: true }).fill('not-a-real-password');

    const submission = button.click();
    try {
        await expect(page.locator('form')).toHaveAttribute('aria-busy', 'true');
        await expect(button).toBeDisabled();
    } finally {
        release();
    }
    await submission;

    await expect(button).toBeEnabled();
    await expect(page.locator('form')).not.toHaveAttribute('aria-busy', 'true');
});
