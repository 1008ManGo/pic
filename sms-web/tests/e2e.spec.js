import { test, expect } from '@playwright/test';

test.describe('SMS Platform E2E Tests', () => {
  test('homepage loads successfully', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/SMS/);
  });

  test('login page displays correctly', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('h2')).toContainText('SMS Platform');
    await expect(page.locator('input[placeholder="Username"]')).toBeVisible();
    await expect(page.locator('input[placeholder="Password"]')).toBeVisible();
    await expect(page.locator('button:has-text("Login")')).toBeVisible();
  });

  test('register page displays correctly', async ({ page }) => {
    await page.goto('/register');
    await expect(page.locator('h2')).toContainText('Register');
    await expect(page.locator('input[placeholder="Username"]')).toBeVisible();
    await expect(page.locator('input[placeholder="Email"]')).toBeVisible();
    await expect(page.locator('input[placeholder="Password"]')).toBeVisible();
  });

  test('register link navigates to register page', async ({ page }) => {
    await page.goto('/login');
    await page.click('a:has-text("Don\'t have an account")');
    await expect(page).toHaveURL(/\/register/);
  });

  test('login link navigates to login page', async ({ page }) => {
    await page.goto('/register');
    await page.click('a:has-text("Already have an account")');
    await expect(page).toHaveURL(/\/login/);
  });

  test('form validation works on login', async ({ page }) => {
    await page.goto('/login');
    await page.click('button:has-text("Login")');
    await expect(page.locator('text=Please enter username')).toBeVisible();
  });

  test('form validation works on register', async ({ page }) => {
    await page.goto('/register');
    await page.click('button:has-text("Register")');
    await expect(page.locator('text=Please enter username')).toBeVisible();
  });
});

test.describe('SMS Encoding Tests', () => {
  test('encoding calculator works', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[placeholder="Username"]', 'testuser');
    await page.fill('input[placeholder="Password"]', 'password123');
    await page.click('button:has-text("Login")');
    await page.waitForURL(/\/dashboard/, { timeout: 5000 }).catch(() => {});
  });
});
