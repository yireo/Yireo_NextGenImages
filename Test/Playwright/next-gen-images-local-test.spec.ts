import {Page} from '@playwright/test';
import {test, expect, configureNextGenImages, getFirstSrcSetUrl, hasConvertor} from './lib/next-gen-images';

/**
 * Baseline with local storage (so, no remote storage): images should be converted and served as before.
 * Requires a convertor like Yireo_Webp2 and the Luma sample data (CMS images on the homepage).
 */
async function gotoHomepageWithImages(page: Page) {
    await page.goto('/');
    const imageCount = await page.locator('img[src*="/media/wysiwyg/"]').count();
    test.skip(imageCount === 0, 'No CMS images on the homepage (Luma sample data is not installed)');
}

test.describe('NextGenImages with local storage', function () {
    test.describe.configure({mode: 'serial'});
    test.skip(!hasConvertor(), 'No convertor module (like Yireo_Webp2) is enabled');

    test('renders a WebP source for CMS images', async function ({page}) {
        await configureNextGenImages(page);
        await gotoHomepageWithImages(page);

        const webpSources = page.locator('picture source[type="image/webp"]');
        expect(await webpSources.count(), 'Number of WebP sources').toBeGreaterThan(0);

        const picture = page.locator('picture:has(source[type="image/webp"])').first();
        const originalImage = picture.locator('img');
        await expect(originalImage, 'Original image as fallback').toHaveCount(1);
        await expect(originalImage).toHaveAttribute('src', /\.(jpg|jpeg|png)$/);

        const webpUrl = getFirstSrcSetUrl(await picture.locator('source[type="image/webp"]').getAttribute('srcset'));
        expect(webpUrl).toMatch(/\.webp$/);

        const response = await page.request.get(webpUrl);
        expect(response.status(), 'WebP response status').toBe(200);
        expect(response.headers()['content-type']).toContain('image/webp');
    });

    test('WebP sources point to the same folder as the original image', async function ({page}) {
        await configureNextGenImages(page);
        await gotoHomepageWithImages(page);

        const pictures = page.locator('picture:has(source[type="image/webp"])');
        const count = await pictures.count();
        expect(count).toBeGreaterThan(0);

        for (let i = 0; i < count; i++) {
            const picture = pictures.nth(i);
            const originalUrl = await picture.locator('img').getAttribute('src');
            const webpUrl = getFirstSrcSetUrl(await picture.locator('source[type="image/webp"]').getAttribute('srcset'));
            expect(webpUrl).toBe(originalUrl!.replace(/\.(jpg|jpeg|png)$/, '.webp'));
        }
    });

    test('does not render WebP sources when disabled', async function ({page}) {
        await configureNextGenImages(page, {'yireo_nextgenimages/settings/enabled': 0});
        await gotoHomepageWithImages(page);

        await expect(page.locator('picture source[type="image/webp"]')).toHaveCount(0);
    });
});
