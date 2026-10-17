import {expect, Page} from '@playwright/test';

export const defaultConfig = {
    'yireo_nextgenimages/settings/enabled': 1,
    'yireo_nextgenimages/settings/convert_images': 1,
    'yireo_nextgenimages/settings/target_directory': 'same_as_source',
    'yireo_webp2/settings/enabled': 1,
};

const token = () => process.env.TEST_TOKEN;

/**
 * Configure the store via the Loki_FunctionalTests endpoint, with `defaultConfig` merged underneath `config`
 */
export async function configureNextGenImages(page: Page, config: Record<string, any> = {}) {
    const requestData = {config: {...defaultConfig, ...config}};
    const response = await page.request.post('/loki_functional_tests/index/configure?token=' + token(), {
        form: {
            config: JSON.stringify(requestData),
        },
    });

    const body = await response.text();
    let data: any;
    try {
        data = JSON.parse(body);
    } catch (error) {
        throw new Error(`Configure endpoint returned no JSON (status ${response.status()}): ${body.slice(0, 500)}`);
    }

    expect(data.error, 'Configure error').toBeUndefined();
}

/**
 * Return the first URL of a srcset attribute
 */
export function getFirstSrcSetUrl(srcSet: string | null): string {
    expect(srcSet, 'srcset attribute').toBeTruthy();
    return srcSet!.split(',')[0].trim().split(' ')[0];
}

export {test, expect} from '@playwright/test';
