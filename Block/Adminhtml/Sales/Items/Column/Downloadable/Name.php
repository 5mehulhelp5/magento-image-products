<?php

namespace DevStone\ImageProducts\Block\Adminhtml\Sales\Items\Column\Downloadable;

use Magento\Downloadable\Model\Link;
use Magento\Downloadable\Model\Link\Purchased;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;

class Name extends \Magento\Downloadable\Block\Adminhtml\Sales\Items\Column\Downloadable\Name
{

    private \DevStone\ImageProducts\Helper\Catalog\Product\Configuration $configuration;

    private EncryptorInterface $encryptor;

    private \Magento\Framework\UrlInterface $frontendUrlBuilder;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\Product\OptionFactory $optionFactory
     * @param \Magento\Downloadable\Model\Link\PurchasedFactory $purchasedFactory
     * @param \Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory $itemsFactory
     * @param \DevStone\ImageProducts\Helper\Catalog\Product\Configuration $configuration
     * @param array $data
     * @param CatalogHelper|null $catalogHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Product\OptionFactory $optionFactory,
        \Magento\Downloadable\Model\Link\PurchasedFactory $purchasedFactory,
        \Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory $itemsFactory,
        \DevStone\ImageProducts\Helper\Catalog\Product\Configuration $configuration,
        EncryptorInterface $encryptor,
        array $data = [],
        ?CatalogHelper $catalogHelper = null
    ) {
        $this->configuration = $configuration;
        $this->encryptor = $encryptor;
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $optionFactory, $purchasedFactory, $itemsFactory, $data, $catalogHelper);
    }

    /**
     * Get order options with image product type specific options.
     *
     * @return array
     */
    public function getOrderOptions()
    {
        $options = parent::getOrderOptions();
        $productOptions = $this->getItem()->getProductOptions();

        if (!empty($productOptions['print_options'])) {
            $options = array_merge(
                $options,
                $this->configuration->getPrintOptions($productOptions['print_options'])
            );
        } elseif (!empty($productOptions['usage_id'])) {
            $options = array_merge(
                $options,
                $this->configuration->getUsageOptions($productOptions['usage_id'], $productOptions['usage_options'])
            );
        }

        return $options;
    }

    public function isPrintProduct():bool {
        $productOptions = $this->getItem()->getProductOptions();
        return ! empty($productOptions['print_options']);
    }

    public function getDownloadUrl():string {
        $item = $this->getItem();
        $expire = time() + (24 * 60 * 60);
        $item_id = $item->getId();
        $data = $this->encryptor->encrypt(join(':', [$expire,$item_id]));
        return $this->getFrontendUrlBuilder()->getUrl(
            'prints/download/art',
            [
                'id' => $data,
                '_scope' => $this->getOrder()->getStore(),
                '_secure' => true,
                '_nosid' => true
            ]
        );
    }


    /**
     * Get frontend URL builder
     *
     * @return \Magento\Framework\UrlInterface
     */
    private function getFrontendUrlBuilder()
    {
        if (!isset($this->frontendUrlBuilder)) {
            $this->frontendUrlBuilder = ObjectManager::getInstance()->get(\Magento\Framework\Url::class);
        }
        return $this->frontendUrlBuilder;
    }
}