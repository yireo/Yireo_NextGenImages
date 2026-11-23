import {Page} from '@playwright/test';
import {test, expect, configureNextGenImages, getRawHtmlOfHandles, hasConvertor} from './lib/next-gen-images';

/**
 * An image with a srcset should get a WebP source with a converted srcset, using local storage.
 * Requires a convertor like Yireo_Webp2.
 */
const handle = 'yireo_nextgenimages_test_srcset';

async function getContainerHtml(page: Page): Promise<string> {
    let html = await getRawHtmlOfHandles(page, [handle]);
    const imageUrls = [...html.matchAll(/(https?:\/\/[^"\s,]+test-(?:loading-attribute|srcset-2x)\.jpg)/g)].map(match => match[1]);
    expect(imageUrls.length, 'Test image URLs').toBeGreaterThan(0);

    for (const imageUrl of new Set(imageUrls)) {
        const response = await page.request.get(imageUrl);
        expect(response.status(), `Response status of ${imageUrl}`).toBe(200);
    }

    html = await getRawHtmlOfHandles(page, [handle]);
    const container = html.match(/<div id="yireo-nextgenimages-test-srcset">([\s\S]*?)<\/div>/);
    expect(container, 'Test container').toBeTruthy();

    return container![1];
}

test.describe('NextGenImages with srcset', function () {
    test.skip(!hasConvertor(), 'No convertor module (like Yireo_Webp2) is enabled');

    test('renders a WebP source with a converted srcset', async function ({page}) {
        await configureNextGenImages(page);
        const html = await getContainerHtml(page);

        const picture = html.match(/<picture[^>]*>([\s\S]*?)<\/picture>/);
        expect(picture, 'Picture').toBeTruthy();

        const webpSource = picture![1].match(/<source[^>]+type="image\/webp"[^>]*>/);
        expect(webpSource, 'WebP source').toBeTruthy();

        const srcSet = webpSource![0].match(/\ssrcset="([^"]+)"/);
        expect(srcSet, 'srcset of WebP source').toBeTruthy();

        const entries = srcSet![1].split(',').map(entry => entry.trim().split(/\s+/));
        expect(entries.map(entry => entry[1]), 'Descriptors').toEqual(['1x', '2x']);
        expect(entries[0][0]).toMatch(/test-loading-attribute\.webp$/);
        expect(entries[1][0]).toMatch(/test-srcset-2x\.webp$/);

        for (const [url] of entries) {
            const response = await page.request.get(url);
            expect(response.status(), `Response status of ${url}`).toBe(200);
            expect(response.headers()['content-type']).toContain('image/webp');
        }

        expect(picture![1], 'Original image as fallback').toMatch(/<img [^>]*srcset="[^"]*test-srcset-2x\.jpg 2x"/);
    });
});
