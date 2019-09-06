<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace DevStone\ImageProducts\Model\Link;

use Magento\Downloadable\Api\LinkRepositoryInterface as LinkRepository;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;

/**
 * Class ReadHandler
 */
class ReadHandler implements ExtensionInterface
{
    /**
     * @var LinkRepository
     */
    protected $linkRepository;

    /**
     * @param LinkRepository $linkRepository
     */
    public function __construct(LinkRepository $linkRepository)
    {
        $this->linkRepository = $linkRepository;
    }

    /**
     * @param object $entity
     * @param array $arguments
     * @return \Magento\Catalog\Api\Data\ProductInterface|object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        /** @var $entity \Magento\Catalog\Api\Data\ProductInterface */
        if ($entity->getTypeId() != \DevStone\ImageProducts\Model\Product\Type::TYPE_ID) {
            return $entity;
        }
        $entityExtension = $entity->getExtensionAttributes();
        $links = $this->linkRepository->getLinksByProduct($entity);
        if ($links) {
            $entityExtension->setDownloadableProductLinks($links);
        }
        $entity->setExtensionAttributes($entityExtension);
        return $entity;
    }
}
