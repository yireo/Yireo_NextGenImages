import {Page} from '@playwright/test';
import {test, expect, configureNextGenImages, getRawHtmlOfHandles, hasConvertor} from './lib/next-gen-images';

/**
 * Many identical images on a single page should each be wrapped exactly once.
 * Requires a convertor like Yireo_Webp2.
 */
const handle = 'yireo_nextgenimages_test_multiple_images';
const imageCount = 100;

async function getContainerHtml(page: Page): Promise<string> {
    let html = await getRawHtmlOfHandles(page, [handle]);
    const imageUrl = html.match(/<img src="([^"]+test-loading-attribute\.jpg)"/);
    expect(imageUrl, 'Test image URL').toBeTruthy();

    const response = await page.request.get(imageUrl![1]);
    expect(response.status(), 'Test image response status').toBe(200);

    html = await getRawHtmlOfHandles(page, [handle]);
    const container = html.match(/<div id="yireo-nextgenimages-test-multiple-images">([\s\S]*?)<\/div>/);
    expect(container, 'Test container').toBeTruthy();

    return container![1];
}

test.describe('NextGenImages with multiple images', function () {
    test.skip(!hasConvertor(), 'No convertor module (like Yireo_Webp2) is enabled');

    test('wraps every image exactly once', async function ({page}) {
        await configureNextGenImages(page);
        const html = await getContainerHtml(page);

        expect(html).not.toContain('data-marker');
        expect(html).not.toContain('yireo_nextgenimages');
        expect(html, 'Nested picture').not.toMatch(/<picture[^>]*>(?:(?!<\/picture>)[\s\S])*<picture/);

        const pictures = [...html.matchAll(/<picture([^>]*)>([\s\S]*?)<\/picture>/g)];
        expect(pictures.length, 'Number of pictures').toBe(imageCount + 1);

        const alts: string[] = [];
        for (const picture of pictures) {
            const imageTags = picture[2].match(/<img [^>]+>/g) ?? [];
            expect(imageTags.length, 'Images per picture').toBe(1);
            const alt = imageTags[0].match(/alt="([^"]+)"/)![1];
            alts.push(alt);

            if (alt === 'existing-picture') {
                expect(picture[2], 'Existing picture is left alone').not.toContain('<source');
                continue;
            }

            expect(picture[2], `WebP source of ${alt}`).toContain('type="image/webp"');
        }

        const expectedAlts = ['existing-picture', ...Array.from({length: imageCount}, (_, i) => 'image-' + (i + 1))];
        expect(alts).toEqual(expectedAlts);

        expect(html, 'Data URI image is not wrapped').toMatch(/<\/picture>\s*<img src="data:image\/gif[^>]+alt="data-uri">/);
    });
});
