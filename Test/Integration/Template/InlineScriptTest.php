<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Template;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 */
class InlineScriptTest extends TestCase
{
    /**
     * @dataProvider getTemplates
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store csp/policies/storefront/scripts/inline 0
     */
    #[DataProvider('getTemplates')]
    public function testIfInlineScriptGetsNonceWithStrictPolicy(string $template, string $expectedScript)
    {
        $html = $this->renderTemplate($template);
        $this->assertStringContainsString($expectedScript, $html);
        $this->assertMatchesRegularExpression('/<script nonce="[^"]+"/', $html);
    }

    /**
     * @dataProvider getTemplates
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store csp/policies/storefront/scripts/inline 1
     */
    #[DataProvider('getTemplates')]
    public function testIfInlineScriptIsRenderedWithInlinePolicy(string $template, string $expectedScript)
    {
        $html = $this->renderTemplate($template);
        $this->assertStringContainsString($expectedScript, $html);
        $this->assertSame(1, substr_count($html, '<script'));
    }

    public static function getTemplates(): array
    {
        return [
            ['Yireo_NextGenImages::hyva/category-additions.phtml', 'originalInitProductItemImage'],
            ['Yireo_NextGenImages::hyva/configurable-additions.phtml', 'originalInitConfigurableOptions'],
            ['Yireo_NextGenImages::hyva/gallery-additions.phtml', 'originalInitGallery'],
        ];
    }

    private function renderTemplate(string $template): string
    {
        $layout = Bootstrap::getObjectManager()->get(LayoutInterface::class);
        $block = $layout->createBlock(Template::class, 'foobar.' . uniqid());
        $block->setTemplate($template);
        return (string)$block->toHtml();
    }
}
