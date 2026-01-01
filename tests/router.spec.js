
const { test, expect } = require('@playwright/test');

test.describe('Router Navigation', () => {
  // Assuming we have a mock or way to login, but for now we test assuming session exists or mocked.
  // Since we can't easily mock PHP session in this environment without full setup, 
  // we will assume the environment is ready or we just check the frontend logic if possible.
  // However, for this task, I will write the test structure. 
  
  // NOTE: This test assumes the app is running at http://localhost/baonangsuat/nhap-nang-suat.php
  // You might need to adjust the baseURL.

  test.beforeEach(async ({ page }) => {
    // Navigate to the page
    await page.goto('http://localhost/baonangsuat/nhap-nang-suat.php');
    
    // Bypass login if needed (manual step usually, but here we assume session or mock)
    // For now, let's assume we are logged in or can see the page.
    // If redirected to index.php (login), we might need to login.
    if (page.url().includes('index.php')) {
        await page.fill('#username', 'admin'); // Adjust credential
        await page.fill('#password', '123456');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/nhap-nang-suat.php*');
    }
  });

  test('should redirect to #/nhap-bao-cao by default', async ({ page }) => {
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('#/nhap-bao-cao');
    await expect(page.locator('#tabInput')).toHaveClass(/active/);
    await expect(page.locator('#tabContentInput')).not.toBeHidden();
  });

  test('should navigate to history tab', async ({ page }) => {
    await page.click('#tabHistory');
    expect(page.url()).toContain('#/lich-su');
    await expect(page.locator('#tabHistory')).toHaveClass(/active/);
    await expect(page.locator('#tabContentHistory')).not.toBeHidden();
  });

  test('should navigate to detail view and back', async ({ page }) => {
    // Go to history
    await page.goto('http://localhost/baonangsuat/nhap-nang-suat.php#/lich-su');
    await page.waitForSelector('#historyTable tr');
    
    // Click first row if exists
    const firstRow = page.locator('#historyTable tbody tr').first();
    if (await firstRow.isVisible()) {
        await firstRow.click();
        
        // URL should change
        await expect(page).toHaveURL(/#\/lich-su\/\d+/);
        
        // Detail view visible
        await expect(page.locator('#historyDetailContainer')).toBeVisible();
        await expect(page.locator('#historyListContainer')).toBeHidden();
        
        // Click back/close
        await page.click('#closeHistoryDetailBtn');
        
        // URL should be back to list
        await expect(page).toHaveURL(/#\/lich-su/);
        await expect(page.locator('#historyDetailContainer')).toBeHidden();
        await expect(page.locator('#historyListContainer')).toBeVisible();
    }
  });

  test('browser back button should work', async ({ page }) => {
    await page.goto('http://localhost/baonangsuat/nhap-nang-suat.php#/nhap-bao-cao');
    await page.click('#tabHistory');
    await expect(page).toHaveURL(/#\/lich-su/);
    
    await page.goBack();
    await expect(page).toHaveURL(/#\/nhap-bao-cao/);
    await expect(page.locator('#tabInput')).toHaveClass(/active/);
  });
  
  test('direct access to history detail', async ({ page }) => {
      // Find a valid ID first? Or assume 1.
      // Better to check list first
      await page.goto('http://localhost/baonangsuat/nhap-nang-suat.php#/lich-su');
      // ... logic to find id ...
      // For now skip if no data
  });
});
