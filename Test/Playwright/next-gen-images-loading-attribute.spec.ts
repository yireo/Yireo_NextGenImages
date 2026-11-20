import {Page} from '@playwright/test';
import {test, expect, configureNextGenImages, getRawHtmlOfHandles, hasConvertor} from './lib/next-gen-images';

/**
 * The browser DOM silently drops duplicate attributes, so the raw HTML is checked instead.
 * Requires a convertor like Yireo_Webp2.
 */
const handle = 'yireo_nextgenimages_test_loading_attribute';

const expectedWithLazyLoading: Record<string, string[]> = {
    'no-attr': ['lazy'],
    'lazy': ['lazy'],
    'lazy-self-closing': ['lazy'],
    'eager': ['eager'],
    'fetchpriority': [],
    'fetchpriority-lazy': ['lazy'],
};

const expectedWithoutLazyLoading: Record<string, string[]> = {
    ...expectedWithLazyLoading,
    'no-attr': [],
};

async function warmupStaticImage(page: Page) {
    const html = await getRawHtmlOfHandles(page, [handle]);
    const imageUrl = html.match(/<img src="([^"]+test-loading-attribute\.jpg)"/);
    expect(imageUrl, 'Test image URL').toBeTruthy();

    const response = await page.request.get(imageUrl![1]);
    expect(response.status(), 'Test image response status').toBe(200);
}

async function getPictureImageTags(page: Page): Promise<Record<string, string>> {
    await warmupStaticImage(page);
    const html = await getRawHtmlOfHandles(page, [handle]);
    const container = html.match(/<div id="yireo-nextgenimages-test-loading-attribute">([\s\S]*?)<\/div>/);
    expect(container, 'Test container').toBeTruthy();

    const pictures = [...container![1].matchAll(/<picture[^>]*>([\s\S]*?)<\/picture>/g)];
    expect(pictures.length, 'Number of pictures').toBe(Object.keys(expectedWithLazyLoading).length);

    const imageTags: Record<string, string> = {};
    for (const picture of pictures) {
        expect(picture[1], 'WebP source').toContain('type="image/webp"');
        const imageTag = picture[1].match(/<img [^>]+>/)![0];
        const alt = imageTag.match(/alt="([^"]+)"/)![1];
        imageTags[alt] = imageTag;
    }

    return imageTags;
}

function assertLoadingAttributes(imageTags: Record<string, string>, expected: Record<string, string[]>) {
    for (const [alt, expectedValues] of Object.entries(expected)) {
        expect(imageTags[alt], `Image "${alt}"`).toBeTruthy();
        const values = [...imageTags[alt].matchAll(/\sloading="([^"]*)"/g)].map(match => match[1]);
        expect(values, `Loading attributes of image "${alt}": ${imageTags[alt]}`).toEqual(expectedValues);
    }
}

test.describe('NextGenImages loading attribute', function () {
    test.describe.configure({mode: 'serial'});
    test.skip(!hasConvertor(), 'No convertor module (like Yireo_Webp2) is enabled');

    test('adds a single loading attribute when lazy loading is enabled', async function ({page}) {
        await configureNextGenImages(page, {'yireo_nextgenimages/settings/lazy_loading': 1});
        assertLoadingAttributes(await getPictureImageTags(page), expectedWithLazyLoading);
    });

    test('keeps existing loading attributes when lazy loading is disabled', async function ({page}) {
        await configureNextGenImages(page, {'yireo_nextgenimages/settings/lazy_loading': 0});
        assertLoadingAttributes(await getPictureImageTags(page), expectedWithoutLazyLoading);
    });
});
