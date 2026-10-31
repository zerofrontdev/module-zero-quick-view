<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\QuickView\Controller\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\FormKey;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\StoreManagerInterface;
use Zero\Core\Controller\PrivateFragmentActionInterface;
use Zero\QuickView\ViewModel\QuickView;

/**
 * quickview/product/view?id=: the product for the dialog, an HTMX fragment
 * (templates/content.phtml). Only a product this store view lists (enabled,
 * on its website, visible in the catalog or in search, as search results
 * offer quick view too); never cached, as it carries the
 * visitor's form key and their group's price.
 */
class View implements PrivateFragmentActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ResultFactory $resultFactory,
        private readonly LayoutFactory $layoutFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly Visibility $visibility,
        private readonly QuickView $quickView
    ) {
    }

    public function execute()
    {
        /** @var Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $product = $this->product();
        if (!$this->quickView->isEnabled() || !$product) {
            $result->setHttpResponseCode(404);
            $result->setContents('<p class="py-6">' . __('This product is not available.') . '</p>');

            return $result;
        }
        $layout = $this->layoutFactory->create();
        $layout->createBlock(FormKey::class, 'formkey');
        $block = $layout->createBlock(Template::class)
            ->setTemplate('Zero_QuickView::content.phtml')
            ->setData('product', $product)
            ->setData('quick_view', $this->quickView);
        $result->setContents($block->toHtml());

        return $result;
    }

    private function product(): ?Product
    {
        $id = (int) $this->request->getParam('id');
        if ($id <= 0) {
            return null;
        }
        try {
            $store = $this->storeManager->getStore();
            /** @var Product $product */
            $product = $this->productRepository->getById($id, false, (int) $store->getId());
        } catch (NoSuchEntityException $e) {
            return null;
        }
        $listed = (int) $product->getStatus() === Status::STATUS_ENABLED
            && in_array((int) $store->getWebsiteId(), array_map('intval', $product->getWebsiteIds()), true)
            && in_array((int) $product->getVisibility(), $this->visibility->getVisibleInSiteIds(), true);

        return $listed ? $product : null;
    }
}
