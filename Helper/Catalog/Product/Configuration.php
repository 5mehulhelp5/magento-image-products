<?php

declare(strict_types=1);

// @codingStandardsIgnoreFile

namespace DevStone\ImageProducts\Helper\Catalog\Product;

use DevStone\FramedPrints\Api\FrameRepositoryInterface;
use DevStone\UsageCalculator\Api\UsageRepositoryInterface;
use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Helper\Product\Configuration\ConfigurationInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Downloadable\Model\Link;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Helper\Product\Configuration as ProductConfiguration;
/**
 * Helper for fetching properties by product configurational item
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Configuration extends AbstractHelper implements
    ConfigurationInterface
{

    const SUBSTRATE_USER_FRIENDLY_MAP = [
        'archival' => "Premium Archival Matte",
        'canvas_unstretched' => 'Unstretched Fine Art Canvas' ,
        'canvas_stretched' => 'Mounted Canvas',
        'canvas_galleryWrap'  => 'Gallery Wrap Canvas',
        'luster' => 'Photo Paper - Semigloss',
        'sunset' => 'Photo Rag - Matte',
        'watercolor' => 'Watercolor Paper',
        'metal_matte_wood' => 'Chroma Print - Matte White-Wood',
        'metal_gloss_wood' => 'Chroma Print - Gloss White-Wood',
        'metal_panel_matte' => 'Chroma Print Panel - Matte White',
        'metal_panel_gloss' => 'Chroma Print Panel - Gloss White',
    ];

    public function __construct(
        Context                                       $context,
        private readonly ProductConfiguration     $productConfig,
        private readonly UsageRepositoryInterface $usageRepository,
        private readonly Json                     $serializer,
        private readonly FrameRepositoryInterface $frameRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Retrieves item links options
     *
     * @param ItemInterface $item
     * @return array
     */
    public function getLinks(ItemInterface $item): array
    {
        $product = $item->getProduct();
        $itemLinks = [];
        $linkIds = $item->getOptionByCode('downloadable_link_ids');
        if ($linkIds) {
            $productLinks = $product->getTypeInstance()->getLinks($product);
            foreach (explode(',', $linkIds->getValue()) as $linkId) {
                if (isset($productLinks[$linkId])) {
                    $itemLinks[] = $productLinks[$linkId];
                }
            }
        }
        return $itemLinks;
    }

    /**
     * Retrieves product links section title
     *
     * @param Product $product
     * @return string
     */
    public function getLinksTitle($product): string
    {
        $title = $product->getLinksTitle();
        if (strlen($title)) {
            return $title;
        }
        return $this->scopeConfig->getValue(Link::XML_PATH_LINKS_TITLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param Image $image
     * @param ItemInterface $item
     */
    public function updateImage(
        Image         $image,
        ItemInterface $item
    ) {
        if ($dataUrl = $item->getOptionByCode('thumbnail')) {
            $image->setImageUrl($dataUrl->getValue());
        }
    }

    /**
     * Retrieves product options
     *
     * @param ItemInterface $item
     * @return array
     */
    public function getOptions(ItemInterface $item): array
    {
        $options = $this->productConfig->getOptions($item);

        if ($item->getOptionByCode('print_options')) {
            $newOptions = $this->getPrintOptions(
                $this->serializer->unserialize(
                    $item->getOptionByCode('print_options')->getValue()
                )
            );
        } elseif (($usageOption = $item->getOptionByCode('usage_id'))) {
            $newOptions = $this->getUsageOptions(
                $usageOption->getValue(),
                $this->serializer->unserialize(
                    $item->getOptionByCode('usage_options')->getValue()
                )
            );
        } else {
            $newOptions = [];
        }

        $options = array_merge($options, $newOptions);

        return $options;
    }

    public function getUsageOptions($usageId, $usageOptions): array
    {
        $terms = '';
        try {
            $usage = $this->usageRepository->getById((int)$usageId);

            $terms = $usage->getTerms();

            foreach ($usage->getOptions() as $option) {
                if (!empty($usageOptions[$option->getId()])) {
                    if (is_numeric($usageOptions[$option->getId()])) {
                        $valueObject = $option->getValueById($usageOptions[$option->getId()]);
                        if (!$valueObject) {
                            $value = $usageOptions[$option->getId()];
                        } else {
                            $value = $valueObject->getTitle();
                        }
                    } else {
                        $value = $usageOptions[$option->getId()];
                    }
                    $terms = str_replace('(' . $option->getTitle() . ')', '<strong>' . $value . '</strong>', $terms);
                }
            }
        } catch (LocalizedException $exc) {
        }

        return [['label' => __('Terms'), 'value' => $terms, 'custom_view' => true]];
    }

    public function getPrintOptions($printOptions): array
    {

        $substrate = $printOptions['substrate'] ?? '';
        if (isset( $printOptions['printOption'] ) && 'canvasPrint' === $printOptions['printOption']) {
            $substrate = 'canvas_' . $printOptions['canvasStyle'];
        } elseif (isset( $printOptions['printOption'] ) && 'metal' === $printOptions['printOption']) {
            $substrate = 'metal_' . $printOptions['metalStyle'];
        }

        if ( $substrate ) {
            $substrate = self::SUBSTRATE_USER_FRIENDLY_MAP[ $substrate ];
        }

        $options = [
            ['label' => __('Substrate'), 'value' => $substrate],
            ['label' => __('Width'), 'value' => $printOptions['imgWI'] . ' ' . __('Inches')],
            ['label' => __('Height'), 'value' => $printOptions['imgHI'] . ' ' . __('Inches')],
        ];

        if (!empty($printOptions['sku'])) {
            $frame = $this->frameRepository->getBySku($printOptions['sku']);

            $options[] = ['label' => __('Frame'), 'value' => $frame?->getDescription() . ' (' . $printOptions['sku'] . ')'];
        }

        if (!empty($printOptions['mat2'])) {
            $options[] = ['label' => __('Top Mat'), 'value' => $printOptions['mat2']];
            $options[] = ['label' => __('Top Mat Size'), 'value' => $printOptions['t'] . ' ' . __('Inches')];
            $options[] = ['label' => __('Bottom Mat'), 'value' => $printOptions['mat1']];
            $options[] = ['label' => __('Bottom Mat Size'), 'value' => $printOptions['off'] . ' ' . __('Inches')];
        } elseif (!empty($printOptions['mat1'])) {
            $options[] = ['label' => __('Top Mat'), 'value' => $printOptions['mat1']];
            $options[] = ['label' => __('Top Mat Size'), 'value' => $printOptions['t'] . ' ' . __('Inches')];
        }

        return $options;
    }
}
