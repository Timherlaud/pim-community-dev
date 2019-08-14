<?php

namespace Akeneo\Pim\Enrichment\Component\Product\Factory\Read;

use Akeneo\Pim\Enrichment\Component\Product\Model\ReadValueCollection;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Structure\Component\Query\PublicApi\AttributeType\Attribute;

/**
 * @author    Anael Chardan <anael.chardan@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ReadValueCollectionFactory extends ValueCollectionFactory
{
    public function createFromStorageFormat(array $rawValues): ReadValueCollection
    {
        $notUsedIdentifier = 'not_used_identifier';

        return $this->createMultipleFromStorageFormat([$notUsedIdentifier => $rawValues])[$notUsedIdentifier];
    }

    protected function createData(Attribute $attribute, ?string $channelCode, ?string $localeCode, $data): ValueInterface
    {
        return $this->valueFactory->createWithoutCheckingData($attribute, $channelCode, $localeCode, $data);
    }

    protected function createCollection(array $values): ReadValueCollection
    {
        return new ReadValueCollection($values);
    }
}
