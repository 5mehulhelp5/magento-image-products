<?php

namespace DevStone\ImageProducts\Plugged\Model\Catalog;

use Magento\Catalog\Model\Product;
use DevStone\ImageProducts\Model\Product\Type as ImageType;
use Magento\Downloadable\Model\Product\Type as DownloadableType;
/**
 * Needed to override getTypeId so it acts like a downloadable product
 *
 * @author David Stone
 */
class ProductPlugin 
{
    public function aroundGetTypeId(Product $product, \Closure $getTypeId)
    {
        $typeId = $getTypeId();
        
//        if(ImageType::TYPE_ID == $typeId) {
//            return DownloadableType::TYPE_DOWNLOADABLE;
//        }
        
        return $typeId;
    }
}
