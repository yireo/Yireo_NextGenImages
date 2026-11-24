import {Page} from '@playwright/test';
import {test, expect, configureNextGenImages, getRawHtmlOfHandles, hasConvertor} from './lib/next-gen-images';

/**
 * Escaped payloads in srcset descriptors, sizes and media attributes should never end up as raw HTML.
 * Requires a convertor like Yireo_Webp2.
 */
const handle = 'yireo_nextgenimages_test_xss';
const containerSelector = '#yireo-nextgenimages-test-xss';

async function getContainerHtml(page: Page): Promise<string> {
    let html = await getRawHtmlOfHandles(page, [handle]);
    const imageUrl = html.match(/<img src="([^"]+test-loading-attribute\.jpg)"/);
    expect(imageUrl, 'Test image URL').toBeTruthy();

    const response = await page.request.get(imageUrl![1]);
    expect(response.status(), 'Test image response status').toBe(200);

    html = await getRawHtmlOfHandles(page, [handle]);
    const container = html.match(/<div id="yireo-nextgenimages-test-xss">([\s\S]*?)<\/div>/);
    expect(container, 'Test container').toBeTruthy();

    return container![1];
}

test.describe('NextGenImages with XSS payloads', function () {
    test.skip(!hasConvertor(), 'No convertor module (like Yireo_Webp2) is enabled');

    test('does not render payloads as raw HTML', async function ({page}) {
        await configureNextGenImages(page);
        const html = await getContainerHtml(page);

        expect(html, 'Raw HTML of container').not.toContain('<svg');
        expect(html, 'Converted images').toMatch(/<picture[^>]*>[\s\S]*?<source[^>]+type="image\/webp"/);
        expect(html, 'Escaped sizes of source').toMatch(/<source[^>]+sizes="100vw&quot;&gt;&lt;svg\/onload=alert\(1\)&gt;"/);
        expect(html, 'Escaped media of source').toMatch(/<source[^>]+media="all&quot;&gt;&lt;svg\/onload=alert\(1\)&gt;"/);

        const dialogs: string[] = [];
        page.on('dialog', async dialog => {
            dialogs.push(dialog.message());
            await dialog.dismiss();
        });

        await page.goto('/loki_functional_tests/index/page?handles=' + handle + '&token=' + process.env.TEST_TOKEN);
        const container = page.locator(containerSelector);
        await expect(container).toBeAttached();
        await expect(container.locator('svg'), 'SVG elements').toHaveCount(0);
        await expect(container.locator('[onload]'), 'Elements with onload').toHaveCount(0);
        expect(dialogs, 'Dialogs').toEqual([]);
    });
});
