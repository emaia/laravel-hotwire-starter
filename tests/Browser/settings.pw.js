import { expect, test } from '@playwright/test';
import { t } from './i18n.js';

const email = process.env.PLAYWRIGHT_TEST_EMAIL;
const password = process.env.PLAYWRIGHT_TEST_PASSWORD;

test.skip(
    process.env.PLAYWRIGHT_SETTINGS_FIXTURE !== '1' || !email || !password,
    'Settings checks require disposable accounts and explicit fixture opt in.',
);

async function logIn(page, testInfo, address = email) {
    await page.goto('/login');
    await page
        .getByRole('textbox', { name: t('Email'), exact: true })
        .fill(address.replace('{project}', testInfo.project.name));
    await page.getByLabel(t('Password'), { exact: true }).fill(password);
    await page.getByRole('button', { name: t('Log in'), exact: true }).click();
    await expect(page).toHaveURL(/\/(?:dashboard|email\/verify)$/);
}

test('profile edits show success feedback and keep the profile selected in navigation', async ({ page }, testInfo) => {
    await logIn(page, testInfo);
    await page.goto('/account/profile');
    const navigation = page.getByRole('navigation', { name: t('Account settings') });
    await page.getByRole('textbox', { name: t('Name'), exact: true }).fill('Pessoa atualizada');
    await page.getByRole('button', { name: t('Save profile'), exact: true }).click();

    await expect(page.getByRole('status')).toContainText(t('Your profile has been updated.'));
    await expect(page.getByRole('textbox', { name: t('Name'), exact: true })).toHaveValue('Pessoa atualizada');
    await expect(navigation.getByRole('link', { name: t('Profile'), exact: true })).toHaveAttribute(
        'aria-current',
        'page',
    );
});

test('the settings theme toggle follows the system until an explicit theme is chosen', async ({ page }, testInfo) => {
    await page.emulateMedia({ colorScheme: 'light' });
    await logIn(page, testInfo);
    await page.goto('/account/profile');
    const toggle = page.getByRole('button', { name: t('Toggle color scheme'), exact: true });

    await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
    await page.emulateMedia({ colorScheme: 'dark' });
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
    await toggle.click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
    await page.reload();

    await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
});

test('profile validation focuses the rejected field without retaining the password', async ({ page }, testInfo) => {
    await logIn(page, testInfo);
    await page.goto('/account/profile');
    await page.getByRole('textbox', { name: t('Email'), exact: true }).fill('rejected@example.test');
    const currentPassword = page.getByLabel(t('Current password'), { exact: true });
    await currentPassword.fill('incorrect');
    await page.getByRole('button', { name: t('Save profile'), exact: true }).click();

    await expect(currentPassword).toHaveAttribute('aria-invalid', 'true');
    await expect(currentPassword).toBeFocused();
    await expect(currentPassword).toHaveValue('');
});

test('unverified accounts can resend verification and correct their email', async ({ page }, testInfo) => {
    test.skip(!process.env.PLAYWRIGHT_UNVERIFIED_EMAIL, 'An unverified disposable account is required.');
    await logIn(page, testInfo, process.env.PLAYWRIGHT_UNVERIFIED_EMAIL);
    await expect(page.getByRole('heading', { name: t('Verify your email'), exact: true })).toBeVisible();
    await page.getByRole('button', { name: t('Resend verification email'), exact: true }).click();
    await expect(page.getByRole('status')).toContainText(t('A new verification link has been sent to your email.'));
    await page.getByRole('link', { name: t('Correct my email'), exact: true }).click();
    await expect(page.getByRole('heading', { name: t('Profile'), exact: true })).toBeVisible();
});
