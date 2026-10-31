<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\QuickView\Test\Unit\Controller\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Zero\QuickView\Controller\Product\View;
use Zero\QuickView\ViewModel\QuickView;

/**
 * Quick view opens what this store view lists: in the catalog or in search
 * (search results offer it too), never a product not visible individually.
 */
class ViewTest extends TestCase
{
    private const DIALOG = '<div class="zero-quick-view">Mona</div>';

    /** @var array{code: ?int, contents: ?string} what the response was given */
    private array $response = ['code' => null, 'contents' => null];

    public function testSearchOnlyProductIsShown(): void
    {
        $this->execute(Visibility::VISIBILITY_IN_SEARCH);

        $this->assertNull($this->response['code']);
        $this->assertSame(self::DIALOG, $this->response['contents']);
    }

    public function testCatalogProductsAreShown(): void
    {
        foreach ([Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH] as $visibility) {
            $this->response = ['code' => null, 'contents' => null];
            $this->execute($visibility);
            $this->assertSame(self::DIALOG, $this->response['contents'], 'visibility ' . $visibility);
        }
    }

    public function testNotVisibleIndividuallyIsNot(): void
    {
        $this->execute(Visibility::VISIBILITY_NOT_VISIBLE);

        $this->assertSame(404, $this->response['code']);
        $this->assertStringContainsString('This product is not available.', (string) $this->response['contents']);
    }

    public function testDisabledOrOtherWebsiteIsNot(): void
    {
        $this->execute(Visibility::VISIBILITY_IN_SEARCH, Status::STATUS_DISABLED);
        $this->assertSame(404, $this->response['code']);

        $this->response = ['code' => null, 'contents' => null];
        $this->execute(Visibility::VISIBILITY_BOTH, Status::STATUS_ENABLED, [2]);
        $this->assertSame(404, $this->response['code']);
    }

    /**
     * @param int[] $websiteIds
     */
    private function execute(int $visibility, int $status = Status::STATUS_ENABLED, array $websiteIds = [1]): void
    {
        $result = $this->createStub(Raw::class);
        $result->method('setHttpResponseCode')->willReturnCallback(function ($code) use ($result) {
            $this->response['code'] = $code;

            return $result;
        });
        $result->method('setContents')->willReturnCallback(function ($contents) use ($result) {
            $this->response['contents'] = (string) $contents;

            return $result;
        });
        $resultFactory = $this->createStub(ResultFactory::class);
        $resultFactory->method('create')->willReturn($result);

        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnMap([['id', null, '7']]);
        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getWebsiteId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $product = $this->createStub(Product::class);
        $product->method('getStatus')->willReturn($status);
        $product->method('getWebsiteIds')->willReturn($websiteIds);
        $product->method('getVisibility')->willReturn((string) $visibility);
        $repository = $this->createStub(ProductRepositoryInterface::class);
        $repository->method('getById')->willReturn($product);

        $block = $this->createStub(Template::class);
        $block->method('setTemplate')->willReturnSelf();
        $block->method('setData')->willReturnSelf();
        $block->method('toHtml')->willReturn(self::DIALOG);
        $layout = $this->createStub(LayoutInterface::class);
        $layout->method('createBlock')->willReturn($block);
        $layoutFactory = $this->createStub(LayoutFactory::class);
        $layoutFactory->method('create')->willReturn($layout);
        $quickView = $this->createStub(QuickView::class);
        $quickView->method('isEnabled')->willReturn(true);

        // The real visibility lists: the fix is which of them the controller asks for.
        $visibilityModel = (new \ReflectionClass(Visibility::class))->newInstanceWithoutConstructor();

        (new View($request, $resultFactory, $layoutFactory, $repository, $storeManager, $visibilityModel, $quickView))
            ->execute();
    }
}
