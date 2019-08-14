<?php

namespace Akeneo\Pim\Enrichment\Component\Product\Factory\Read;

use Akeneo\Pim\Enrichment\Component\Product\Factory\EmptyValuesCleaner;
use Akeneo\Pim\Enrichment\Component\Product\Factory\NonExistentValuesFilter\ChainedNonExistentValuesFilterInterface;
use Akeneo\Pim\Enrichment\Component\Product\Factory\NonExistentValuesFilter\OnGoingFilteredRawValues;
use Akeneo\Pim\Enrichment\Component\Product\Factory\TransformRawValuesCollections;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Structure\Component\Query\PublicApi\AttributeType\Attribute;
use Akeneo\Pim\Structure\Component\Query\PublicApi\AttributeType\GetAttributes;

/**
 * @author    Anael Chardan <anael.chardan@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class ValueCollectionFactory
{
    /** @var ValueFactory */
    protected $valueFactory;

    /** @var GetAttributes */
    private $getAttributeByCodes;

    /** @var ChainedNonExistentValuesFilterInterface */
    private $chainedNonExistentValuesFilter;

    /** @var EmptyValuesCleaner */
    private $emptyValuesCleaner;

    /** @var TransformRawValuesCollections */
    private $transformRawValuesCollections;

    public function __construct(
        ValueFactory $valueFactory,
        GetAttributes $getAttributeByCodes,
        ChainedNonExistentValuesFilterInterface $chainedNonExistentValuesFilter,
        EmptyValuesCleaner $emptyValuesCleaner,
        TransformRawValuesCollections $transformRawValuesCollections
    ) {
        $this->valueFactory = $valueFactory;
        $this->getAttributeByCodes = $getAttributeByCodes;
        $this->chainedNonExistentValuesFilter = $chainedNonExistentValuesFilter;
        $this->emptyValuesCleaner = $emptyValuesCleaner;
        $this->transformRawValuesCollections = $transformRawValuesCollections;
    }

    public function createMultipleFromStorageFormat(array $rawValueCollections): array
    {
        $rawValueCollectionsIndexedByType = $this->transformRawValuesCollections->toValueCollectionsIndexedByType($rawValueCollections);
        $valueCollections = [];

        if (empty($rawValueCollectionsIndexedByType)) {
            foreach (array_keys($rawValueCollections) as $identifier) {
                $valueCollections[$identifier] = $this->createCollection([]);
            }

            return $valueCollections;
        }

        $filtered = $this->chainedNonExistentValuesFilter->filterAll(
            OnGoingFilteredRawValues::fromNonFilteredValuesCollectionIndexedByType($rawValueCollectionsIndexedByType)
        );

        $rawValueCollection = $filtered->toRawValueCollection();

        $cleanRawValueCollection = $this->emptyValuesCleaner->cleanAllValues($rawValueCollection);

        $valueCollections = $this->createValues($cleanRawValueCollection);

        $identifiersWithOnlyUnknownAttributes = array_diff(array_keys($rawValueCollections), array_keys($valueCollections));

        foreach ($identifiersWithOnlyUnknownAttributes as $identifier) {
            $valueCollections[$identifier] = $this->createCollection([]);
        }

        return $valueCollections;
    }

    private function getAttributesUsedByProducts(array $rawValueCollections): array
    {
        $attributeCodes = [];

        foreach ($rawValueCollections as $productIdentifier => $rawValues) {
            foreach (array_keys($rawValues) as $attributeCode) {
                $attributeCodes[] = (string) $attributeCode;
            }
        }

        $attributes = $this->getAttributeByCodes->forCodes(array_unique($attributeCodes));

        return $attributes;
    }

    private function createValues(array $rawValueCollections): array
    {
        $entities = [];
        $attributes = $this->getAttributesUsedByProducts($rawValueCollections);

        foreach ($rawValueCollections as $productIdentifier => $valueCollection) {
            $values = [];

            foreach ($valueCollection as $attributeCode => $channelRawValue) {
                $attribute = $attributes[$attributeCode];

                foreach ($channelRawValue as $channelCode => $localeRawValue) {
                    if ('<all_channels>' === $channelCode) {
                        $channelCode = null;
                    }

                    foreach ($localeRawValue as $localeCode => $data) {
                        if ('<all_locales>' === $localeCode) {
                            $localeCode = null;
                        }

                        // TODO : CATCH EXCEPTIONS
                        $values[] = $this->createData($attribute, $channelCode, $localeCode, $data);
                    }
                }
            }

            $entities[$productIdentifier] = $this->createCollection($values);
        }

        return $entities;
    }

    abstract protected function createData(Attribute $attribute, ?string $channelCode, ?string $localeCode, $data): ValueInterface;

    abstract protected function createCollection(array $values);
}
