<?php

namespace Akeneo\Pim\Enrichment\Component\Product\Factory\Write\Value;

use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;

/**
 * @author    Benoit Jacquemont (benoit@akeneo.com)
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
abstract class AbstractValueFactory implements ValueFactoryInterface
{
    /** @var string */
    protected $productValueClass;

    /** @var string */
    protected $supportedAttributeType;

    public function __construct(string $productValueClass, string $supportedAttributeType)
    {
        $this->productValueClass = $productValueClass;
        $this->supportedAttributeType = $supportedAttributeType;
    }

    /**
     * {@inheritdoc}
     */
    public function create(
        AttributeInterface $attribute,
        ?string $channelCode,
        ?string $localeCode,
        $data,
        bool $ignoreUnknownData = false
    ): ValueInterface {
        $data = $this->prepareData($attribute, $data, $ignoreUnknownData);

        if ($attribute->isScopable() && $attribute->isLocalizable()) {
            $value = $this->productValueClass::scopableLocalizableValue($attribute->getCode(), $data, $channelCode, $localeCode);
        } else {
            if ($attribute->isScopable()) {
                $value = $this->productValueClass::scopablevalue($attribute->getCode(), $data, $channelCode);
            } elseif ($attribute->isLocalizable()) {
                $value = $this->productValueClass::localizableValue($attribute->getCode(), $data, $localeCode);
            } else {
                $value = $this->productValueClass::value($attribute->getCode(), $data);
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $attributeType): bool
    {
        return $attributeType === $this->supportedAttributeType;
    }

    /**
     * Prepare the data and check if everything is correct
     *
     * @param AttributeInterface $attribute
     * @param mixed              $data
     * @param bool               $ignoreUnknownData
     *
     * @return mixed
     */
    abstract protected function prepareData(AttributeInterface $attribute, $data, bool $ignoreUnknownData);
}
