<?php

namespace Specification\Akeneo\Pim\Enrichment\Component\Product\Normalizer\Indexing\ProductAndProductModel;

use Akeneo\Channel\Component\Repository\ChannelRepositoryInterface;
use Akeneo\Channel\Component\Repository\LocaleRepositoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\Projection\ProductCompleteness;
use Akeneo\Pim\Enrichment\Component\Product\Model\Projection\ProductCompletenessCollection;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Enrichment\Component\Product\Query\GetProductCompletenesses;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Structure\Component\Model\FamilyInterface;
use Akeneo\Pim\Structure\Component\Model\FamilyVariantInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\WriteValueCollection;
use Akeneo\Pim\Enrichment\Component\Product\Normalizer\Indexing\ProductAndProductModel\ProductModelNormalizer;
use Akeneo\Pim\Enrichment\Component\Product\Normalizer\Indexing\ProductAndProductModel\ProductPropertiesNormalizer;
use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ProductPropertiesNormalizerSpec extends ObjectBehavior
{
    function let(
        SerializerInterface $serializer,
        ChannelRepositoryInterface $channelRepository,
        LocaleRepositoryInterface $localeRepository,
        GetProductCompletenesses $getProductCompletenesses
    ) {
        $this->beConstructedWith(
            $channelRepository,
            $localeRepository,
            $getProductCompletenesses
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ProductPropertiesNormalizer::class);
    }

    function it_support_products_and_variant_products(
        ProductInterface $product,
        ProductInterface $variantProduct
    ) {
        $this->supportsNormalization($product, 'whatever')->shouldReturn(false);
        $this->supportsNormalization($product, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)
            ->shouldReturn(true);

        $this->supportsNormalization($variantProduct, 'whatever')->shouldReturn(false);
        $this->supportsNormalization($variantProduct, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)
            ->shouldReturn(true);

        $this->supportsNormalization(new \stdClass(), 'whatever')->shouldReturn(false);
        $this->supportsNormalization(new \stdClass(), ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)
            ->shouldReturn(false);
    }

    function it_normalizes_product_properties_with_minimum_filled_fields_and_values(
        $serializer,
        $getProductCompletenesses,
        ProductInterface $product,
        WriteValueCollection $valueCollection
    ) {
        $serializer->implement(NormalizerInterface::class);
        $this->setSerializer($serializer);

        $product->getId()->willReturn(67);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $family = null;

        $product->isVariant()->willReturn(false);
        $product->getIdentifier()->willReturn('sku-001');
        $product->getFamily()->willReturn($family);
        $serializer->normalize($family, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)
            ->willReturn(null);

        $product->getCreated()->willReturn($now);
        $serializer->normalize(
            $product->getWrappedObject()->getCreated(),
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX
        )->willReturn($now->format('c'));

        $product->getUpdated()->willReturn($now);
        $serializer->normalize(
            $product->getWrappedObject()->getUpdated(),
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX
        )->willReturn($now->format('c'));

        $product->isEnabled()->willReturn(false);
        $product->getValues()->willReturn($valueCollection);
        $product->getFamily()->willReturn(null);
        $product->getGroupCodes()->willReturn([]);
        $product->getCategoryCodes()->willReturn([]);
        $valueCollection->isEmpty()->willReturn(true);

        $completeness = new ProductCompleteness('channelCode', 'localeCode', 0, []);
        $completenessCollection = new ProductCompletenessCollection(67, [$completeness]);
        $getProductCompletenesses->fromProductId(67)->willReturn($completenessCollection);

        $serializer->normalize(
            $completenessCollection,
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX,
            []
        )->willReturn(['normalized_completeness']);

        $this->normalize($product, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)->shouldReturn(
            [
                'id' => 'product_67',
                'identifier' => 'sku-001',
                'created' => $now->format('c'),
                'updated' => $now->format('c'),
                'family' => null,
                'enabled' => false,
                'categories' => [],
                'categories_of_ancestors' => [],
                'groups' => [],
                'completeness' => ['normalized_completeness'],
                'family_variant' => null,
                'parent' => null,
                'values' => [],
                'ancestors' => [
                    'ids' => [],
                    'codes' => [],
                    'labels' => [],
                ],
                'label' => [],
            ]
        );
    }

    function it_normalizes_product_properties_with_fields_and_values(
        $serializer,
        $getProductCompletenesses,
        ProductInterface $product,
        WriteValueCollection $valueCollection,
        FamilyInterface $family,
        AttributeInterface $sku
    ) {
        $serializer->implement(NormalizerInterface::class);
        $this->setSerializer($serializer);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $product->isVariant()->willReturn(false);
        $product->getId()->willReturn(67);
        $product->getIdentifier()->willReturn('sku-001');

        $product->getCreated()->willReturn($now);
        $serializer->normalize(
            $product->getWrappedObject()->getCreated(),
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX
        )->willReturn($now->format('c'));

        $product->getUpdated()->willReturn($now);
        $serializer->normalize(
            $product->getWrappedObject()->getUpdated(),
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX
        )->willReturn($now->format('c'));

        $product->getFamily()->willReturn($family);
        $family->getAttributeAsLabel()->willReturn($sku);
        $sku->getCode()->willReturn('sku');
        $serializer
            ->normalize($family, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)
            ->willReturn([
                'code'   => 'family',
                'labels' => [
                    'fr_FR' => 'Une famille',
                    'en_US' => 'A family',
                ],
            ]);

        $product->isEnabled()->willReturn(true);
        $product->getGroupCodes()->willReturn(['first_group', 'second_group', 'another_group']);
        $product->getCategoryCodes()->willReturn(
            [
                'first_category',
                'second_category',
            ]
        );

        $completeness = new ProductCompleteness('ecommerce', 'en_US', 3, ['fake_attr']);
        $completenessCollection = new ProductCompletenessCollection(67, [$completeness]);
        $getProductCompletenesses->fromProductId(67)->willReturn($completenessCollection);
        $serializer->normalize($completenessCollection, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX,
            [])->willReturn(
            [
                'ecommerce' => [
                    'en_US' => [
                        66,
                    ],
                ],
            ]
        );

        $product->getValues()
            ->shouldBeCalledTimes(2)
            ->willReturn($valueCollection);
        $valueCollection->isEmpty()->willReturn(false);
        $serializer->normalize($valueCollection, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX, [])
            ->willReturn(
                [
                    'a_size-decimal' => [
                        '<all_channels>' => [
                            '<all_locales>' => '10.51',
                        ],
                    ],
                ]
            );

        $this->normalize($product, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)->shouldReturn(
            [
                'id' => 'product_67',
                'identifier' => 'sku-001',
                'created' => $now->format('c'),
                'updated' => $now->format('c'),
                'family' => [
                    'code' => 'family',
                    'labels' => [
                        'fr_FR' => 'Une famille',
                        'en_US' => 'A family',
                    ],
                ],
                'enabled' => true,
                'categories' => ['first_category', 'second_category'],
                'categories_of_ancestors' => [],
                'groups' => ['first_group', 'second_group', 'another_group'],
                'in_group' => [
                    'first_group' => true,
                    'second_group' => true,
                    'another_group' => true,
                ],
                'completeness' => [
                    'ecommerce' => [
                        'en_US' => [
                            66,
                        ],
                    ],
                ],
                'family_variant' => null,
                'parent' => null,
                'values' => [
                    'a_size-decimal' => [
                        '<all_channels>' => [
                            '<all_locales>' => '10.51',
                        ],
                    ],
                ],
                'ancestors' => [
                    'ids' => [],
                    'codes' => [],
                    'labels' => [],
                ],
                'label' => [],
            ]
        );
    }

    function it_normalizes_variant_product_properties_with_fields_and_values(
        $serializer,
        $localeRepository,
        $channelRepository,
        $getProductCompletenesses,
        AttributeInterface $sku,
        ProductInterface $variantProduct,
        WriteValueCollection $valueCollection,
        FamilyInterface $family,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $subProductModel,
        ProductModelInterface $rootProductModel,
        ValueInterface $frEcomSku,
        ValueInterface $frPrintSku,
        ValueInterface $enEcomSku,
        ValueInterface $enPrintSku
    ) {
        $serializer->implement(NormalizerInterface::class);
        $this->setSerializer($serializer);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $afterNow = new \DateTime('now', new \DateTimeZone('UTC'));
        $afterNow->add(new \DateInterval('P10D'));
        $beforeNow = new \DateTime('now', new \DateTimeZone('UTC'));
        $beforeNow->sub(new \DateInterval('P10D'));

        $variantProduct->isVariant()->willReturn(true);
        $variantProduct->getId()->willReturn(67);
        $variantProduct->getIdentifier()->willReturn('sku-001');

        $variantProduct->getCreated()->willReturn($now);
        $serializer->normalize(
            $variantProduct->getWrappedObject()->getCreated(),
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX
        )->willReturn($now->format('c'));

        $variantProduct->getUpdated()->willReturn($now);

        $variantProduct->getFamily()->willReturn($family);
        $serializer
            ->normalize($family, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)
            ->willReturn([
                'code' => 'family',
                'labels' => [
                    'fr_FR' => 'Une famille',
                    'en_US' => 'A family',
                ],
            ]);

        $variantProduct->isEnabled()->willReturn(true);
        $variantProduct->getGroupCodes()->willReturn(['first_group', 'second_group', 'another_group']);
        $variantProduct->getCategoryCodes()->willReturn(
            [
                'first_category',
                'second_category',
            ]
        );

        $completeness1 = new ProductCompleteness('ecommerce', 'en_US', 3, ['fake_attr']);
        $completeness2 = new ProductCompleteness('ecommerce', 'fr_FR', 3, ['fake_attr']);
        $completeness3 = new ProductCompleteness('print', 'en_US', 3, ['fake_attr']);
        $completeness4 = new ProductCompleteness('print', 'fr_FR', 3, ['fake_attr']);
        $completenessCollection = new ProductCompletenessCollection(67, [
            $completeness1,
            $completeness2,
            $completeness3,
            $completeness4
        ]);
        $getProductCompletenesses->fromProductId(67)->willReturn($completenessCollection);
        $serializer->normalize(
            $completenessCollection,
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX,
            []
        )->willReturn(
            [
                'ecommerce' => [
                    'en_US' => [
                        66,
                    ],
                    'fr_FR' => [
                        66,
                    ],
                ],
                'print' => [
                    'en_US' => [
                        66,
                    ],
                    'fr_FR' => [
                        66,
                    ],
                ]
            ]
        );

        $variantProduct->getValues()
            ->shouldBeCalledTimes(2)
            ->willReturn($valueCollection);
        $valueCollection->isEmpty()->willReturn(false);
        $serializer->normalize($valueCollection, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX, [])
            ->willReturn(
                [
                    'a_size-decimal' => [
                        '<all_channels>' => [
                            '<all_locales>' => '10.51',
                        ],
                    ],
                ]
            );

        $variantProduct->getFamilyVariant()->willReturn($familyVariant);

        $variantProduct->getParent()->willReturn($subProductModel);
        $subProductModel->getId()->willReturn(4);
        $subProductModel->getCode()->willReturn('model-tshirt-xs');
        $subProductModel->getParent()->willReturn($rootProductModel);
        $subProductModel->getCategoryCodes()->willReturn(['second_category']);
        $subProductModel->getUpdated()->willReturn($afterNow);
        $rootProductModel->getId()->willReturn(1);
        $rootProductModel->getCode()->willReturn('model-tshirt');
        $rootProductModel->getParent()->willReturn(null);
        $rootProductModel->getUpdated()->willReturn($beforeNow);

        $family->getAttributeAsLabel()->willReturn($sku);
        $sku->getCode()->willReturn('sku');
        $sku->isScopable()->willReturn(true);
        $sku->isLocalizable()->willReturn(true);

        $localeRepository->getActivatedLocaleCodes()->willReturn(['fr_FR', 'en_US']);
        $channelRepository->getChannelCodes()->willReturn(['ecommerce', 'print']);

        $variantProduct->getValue('sku', 'fr_FR', 'ecommerce')->willReturn($frEcomSku);
        $frEcomSku->getData()->willReturn('Un sku FR ecommerce');

        $variantProduct->getValue('sku', 'fr_FR', 'print')->willReturn($frPrintSku);
        $frPrintSku->getData()->willReturn('Un sku FR print');

        $variantProduct->getValue('sku', 'en_US', 'ecommerce')->willReturn($enEcomSku);
        $enEcomSku->getData()->willReturn('Sku EN ecommerce');

        $variantProduct->getValue('sku', 'en_US', 'print')->willReturn($enPrintSku);
        $enPrintSku->getData()->willReturn('Sku EN print');

        $serializer->normalize(
            $subProductModel->getWrappedObject()->getUpdated(),
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX
        )->willReturn($afterNow->format('c'));

        $this->normalize($variantProduct,
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)->shouldReturn(
            [
                'id' => 'product_67',
                'identifier' => 'sku-001',
                'created' => $now->format('c'),
                'updated' => $afterNow->format('c'),
                'family' => [
                    'code' => 'family',
                    'labels' => [
                        'fr_FR' => 'Une famille',
                        'en_US' => 'A family',
                    ],
                ],
                'enabled' => true,
                'categories' => ['first_category', 'second_category'],
                'categories_of_ancestors' => ['second_category'],
                'groups' => ['first_group', 'second_group', 'another_group'],
                'in_group' => [
                    'first_group' => true,
                    'second_group' => true,
                    'another_group' => true,
                ],
                'completeness' => [
                    'ecommerce' => [
                        'en_US' => [66],
                        'fr_FR' => [66],
                    ],
                    'print' => [
                        'en_US' => [66],
                        'fr_FR' => [66],
                    ]
                ],
                'family_variant' => null,
                'parent' => 'model-tshirt-xs',
                'values' => [
                    'a_size-decimal' => [
                        '<all_channels>' => [
                            '<all_locales>' => '10.51',
                        ],
                    ],
                ],
                'ancestors' => [
                    'ids' => ['product_model_4', 'product_model_1'],
                    'codes' => ['model-tshirt-xs', 'model-tshirt'],
                    'labels' => [
                        'ecommerce' => [
                            'fr_FR' => 'Un sku FR ecommerce',
                            'en_US' => 'Sku EN ecommerce',
                        ],
                        'print' => [
                            'fr_FR' => 'Un sku FR print',
                            'en_US' => 'Sku EN print',
                        ]
                    ],
                ],
                'label' => [],
            ]
        );
    }

    function it_normalizes_variant_product_properties_with_minimum_filled_fields_and_values(
        $serializer,
        $channelRepository,
        $getProductCompletenesses,
        ProductInterface $variantProduct,
        WriteValueCollection $valueCollection,
        FamilyInterface $family,
        AttributeInterface $sku,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $subProductModel,
        ProductModelInterface $rootProductModel,
        ValueInterface $ecommerceSku,
        ValueInterface $printSku
    ) {
        $serializer->implement(NormalizerInterface::class);
        $this->setSerializer($serializer);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $variantProduct->isVariant()->willReturn(true);
        $variantProduct->getId()->willReturn(67);
        $variantProduct->getIdentifier()->willReturn('sku-001');
        $variantProduct->getFamily()->willReturn($family);
        $family->getAttributeAsLabel()->willReturn($sku);
        $sku->getCode()->willReturn('sku');
        $sku->isScopable()->willReturn(true);
        $sku->isLocalizable()->willReturn(false);

        $variantProduct->getCreated()->willReturn($now);
        $serializer->normalize(
            $variantProduct->getWrappedObject()->getCreated(),
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX
        )->willReturn($now->format('c'));

        $variantProduct->getUpdated()->willReturn($now);
        $serializer->normalize(
            $variantProduct->getWrappedObject()->getUpdated(),
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX
        )->willReturn($now->format('c'));

        $familyVariant->getCode()->willReturn('family_variant_A');
        $variantProduct->getFamily()->willReturn($family);
        $variantProduct->getFamilyVariant()->willReturn($familyVariant);
        $variantProduct->isEnabled()->willReturn(false);
        $variantProduct->getValues()->willReturn($valueCollection);
        $variantProduct->getGroupCodes()->willReturn([]);
        $variantProduct->getCategoryCodes()->willReturn([]);
        $valueCollection->isEmpty()->willReturn(true);

        $serializer->normalize($family,
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)->willReturn(
            [
                'code' => 'family',
                'labels' => [
                    'fr_FR' => 'Une famille',
                    'en_US' => 'A family',
                ],
            ]
        );

        $completeness = new ProductCompleteness('ecommerce', 'en_US', 0, []);
        $completenessCollection = new ProductCompletenessCollection(67, [$completeness]);
        $getProductCompletenesses->fromProductId(67)->willReturn($completenessCollection);
        $serializer->normalize($completenessCollection, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX, [])
            ->willReturn(['normalized_completeness']);

        $variantProduct->getParent()->willReturn($subProductModel);
        $subProductModel->getId()->willReturn(4);
        $subProductModel->getCode()->willReturn('model-tshirt-xs');
        $subProductModel->getParent()->willReturn($rootProductModel);
        $subProductModel->getCategoryCodes()->willReturn([]);
        $subProductModel->getUpdated()->willReturn($now);
        $rootProductModel->getId()->willReturn(1);
        $rootProductModel->getCode()->willReturn('model-tshirt');
        $rootProductModel->getParent()->willReturn(null);
        $rootProductModel->getUpdated()->willReturn($now);

        $channelRepository->getChannelCodes()->willReturn(['ecommerce', 'print']);

        $variantProduct->getValue('sku', null, 'ecommerce')->willReturn($ecommerceSku);
        $ecommerceSku->getData()->willReturn('ecommerce SKU');

        $variantProduct->getValue('sku', null, 'print')->willReturn($printSku);
        $printSku->getData()->willReturn('print SKU');

        $this->normalize($variantProduct,
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)->shouldReturn([
                'id' => 'product_67',
                'identifier' => 'sku-001',
                'created' => $now->format('c'),
                'updated' => $now->format('c'),
                'family' => [
                    'code' => 'family',
                    'labels' => [
                        'fr_FR' => 'Une famille',
                        'en_US' => 'A family',
                    ],
                ],
                'enabled' => false,
                'categories' => [],
                'categories_of_ancestors' => [],
                'groups' => [],
                'completeness' => ['normalized_completeness'],
                'family_variant' => 'family_variant_A',
                'parent' => 'model-tshirt-xs',
                'values' => [],
                'ancestors' => [
                    'ids' => ['product_model_4', 'product_model_1'],
                    'codes' => ['model-tshirt-xs', 'model-tshirt'],
                    'labels' => [
                        'ecommerce' => [
                            '<all_locales>' => 'ecommerce SKU',
                        ],
                        'print' => [
                            '<all_locales>' => 'print SKU',
                        ]
                    ],
                ],
                'label' => [],
            ]
        );
    }

    function it_normalizes_variant_product_properties_with_fields_and_values_and_its_parents_values(
        $serializer,
        $localeRepository,
        $getProductCompletenesses,
        ProductInterface $variantProduct,
        WriteValueCollection $valueCollection,
        FamilyInterface $family,
        AttributeInterface $sku,
        FamilyVariantInterface $familyVariant,
        ProductModelInterface $rootProductModel,
        ValueInterface $frSku,
        ValueInterface $enSku
    ) {
        $serializer->implement(NormalizerInterface::class);
        $this->setSerializer($serializer);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $variantProduct->isVariant()->willReturn(true);
        $variantProduct->getId()->willReturn(67);
        $variantProduct->getIdentifier()->willReturn('sku-001');
        $variantProduct->getFamily()->willReturn($family);
        $family->getAttributeAsLabel()->willReturn($sku);
        $sku->getCode()->willReturn('sku');
        $sku->isScopable()->willReturn(false);
        $sku->isLocalizable()->willReturn(true);

        $variantProduct->getCreated()->willReturn($now);
        $serializer->normalize(
            $variantProduct->getWrappedObject()->getCreated(),
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX
        )->willReturn($now->format('c'));

        $variantProduct->getUpdated()->willReturn($now);
        $serializer->normalize(
            $variantProduct->getWrappedObject()->getUpdated(),
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX
        )->willReturn($now->format('c'));

        $variantProduct->getFamily()->willReturn($family);
        $serializer
            ->normalize($family, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)
            ->willReturn([
                'code' => 'family',
                'labels' => [
                    'fr_FR' => 'Une famille',
                    'en_US' => 'A family',
                ],
            ]);

        $familyVariant->getCode()->willReturn('family_variant_A');
        $variantProduct->getFamily()->willReturn($family);
        $variantProduct->getFamilyVariant()->willReturn($familyVariant);
        $variantProduct->isEnabled()->willReturn(true);
        $variantProduct->getGroupCodes()->willReturn(['first_group', 'second_group', 'another_group']);
        $variantProduct->getCategoryCodes()->willReturn(
            [
                'first_category',
                'second_category',
            ]
        );

        $completeness = new ProductCompleteness('ecommerce', 'en_US', 3, ['fake_attr']);
        $completenessCollection = new ProductCompletenessCollection(67, [$completeness]);
        $getProductCompletenesses->fromProductId(67)->willReturn($completenessCollection);
        $serializer->normalize($completenessCollection, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX,
            [])->willReturn(
            [
                'ecommerce' => [
                    'en_US' => [
                        66,
                    ],
                ],
            ]
        );

        $variantProduct->getValues()
            ->shouldBeCalledTimes(2)
            ->willReturn($valueCollection);
        $valueCollection->isEmpty()->willReturn(false);
        $serializer->normalize($valueCollection, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX,
            [])
            ->willReturn(
                [
                    'a_size-decimal' => [
                        '<all_channels>' => [
                            '<all_locales>' => '10.51',
                        ],
                    ],
                    'a_date-date' => [
                        '<all_channels>' => [
                            '<all_locales>' => '2017-05-05',
                        ],
                    ],
                    'a_simple_select-option' => [
                        '<all_channels>' => [
                            '<all_locales>' => 'OPTION_A',
                        ],
                    ],
                ]
            );

        $variantProduct->getParent()->willReturn($rootProductModel);
        $rootProductModel->getId()->willReturn(1);
        $rootProductModel->getCode()->willReturn('model-tshirt');
        $rootProductModel->getParent()->willReturn(null);
        $rootProductModel->getCategoryCodes()->willReturn(['first_category']);
        $rootProductModel->getUpdated()->willReturn($now);

        $localeRepository->getActivatedLocaleCodes()->willReturn(['fr_FR', 'en_US']);

        $variantProduct->getValue('sku', 'fr_FR')->willReturn($frSku);
        $frSku->getData()->willReturn('fr_FR SKU');

        $variantProduct->getValue('sku', 'en_US')->willReturn($enSku);
        $enSku->getData()->willReturn('en_US SKU');

        $this->normalize($variantProduct,
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)->shouldReturn(
            [
                'id' => 'product_67',
                'identifier' => 'sku-001',
                'created' => $now->format('c'),
                'updated' => $now->format('c'),
                'family' => [
                    'code' => 'family',
                    'labels' => [
                        'fr_FR' => 'Une famille',
                        'en_US' => 'A family',
                    ],
                ],
                'enabled' => true,
                'categories' => ['first_category', 'second_category'],
                'categories_of_ancestors' => ['first_category'],
                'groups' => ['first_group', 'second_group', 'another_group'],
                'in_group' => [
                    'first_group' => true,
                    'second_group' => true,
                    'another_group' => true,
                ],
                'completeness' => [
                    'ecommerce' => [
                        'en_US' => [
                            66,
                        ],
                    ],
                ],
                'family_variant' => 'family_variant_A',
                'parent' => 'model-tshirt',
                'values' => [
                    'a_size-decimal' => [
                        '<all_channels>' => [
                            '<all_locales>' => '10.51',
                        ],
                    ],
                    'a_date-date' => [
                        '<all_channels>' => [
                            '<all_locales>' => '2017-05-05',
                        ],
                    ],
                    'a_simple_select-option' => [
                        '<all_channels>' => [
                            '<all_locales>' => 'OPTION_A',
                        ],
                    ],
                ],
                'ancestors' => [
                    'ids' => ['product_model_1'],
                    'codes' => ['model-tshirt'],
                    'labels' => [
                        '<all_channels>' => [
                            'fr_FR' => 'fr_FR SKU',
                            'en_US' => 'en_US SKU',
                        ],
                    ],
                ],
                'label' => [],
            ]
        );
    }

    function it_adds_extra_data_with_optionnal_normalizers(
        $serializer,
        $channelRepository,
        $localeRepository,
        $getProductCompletenesses,
        NormalizerInterface $normalizer1,
        NormalizerInterface $normalizer2,
        ProductInterface $product,
        WriteValueCollection $valueCollection
    ) {
        $this->beConstructedWith($channelRepository, $localeRepository, $getProductCompletenesses, [$normalizer1, $normalizer2]);
        $serializer->implement(NormalizerInterface::class);
        $this->setSerializer($serializer);

        $product->getId()->willReturn(67);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $family = null;

        $product->isVariant()->willReturn(false);
        $product->getIdentifier()->willReturn('sku-001');
        $product->getFamily()->willReturn($family);
        $serializer->normalize($family, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)
            ->willReturn(null);

        $product->getCreated()->willReturn($now);
        $serializer->normalize(
            $product->getWrappedObject()->getCreated(),
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX
        )->willReturn($now->format('c'));

        $product->getUpdated()->willReturn($now);
        $serializer->normalize(
            $product->getWrappedObject()->getUpdated(),
            ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX
        )->willReturn($now->format('c'));

        $product->isEnabled()->willReturn(false);
        $product->getValues()->willReturn($valueCollection);
        $product->getFamily()->willReturn(null);
        $product->getGroupCodes()->willReturn([]);
        $product->getCategoryCodes()->willReturn([]);
        $valueCollection->isEmpty()->willReturn(true);

        $completeness = new ProductCompleteness('channelCode', 'localeCode', 0, []);
        $completenessCollection = new ProductCompletenessCollection(67, [$completeness]);
        $getProductCompletenesses->fromProductId(67)->willReturn($completenessCollection);

        $serializer->normalize($completenessCollection, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX,
            [])->willReturn(['normalized_completeness']);

        $normalizer1
            ->normalize($product, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX, [])
            ->willReturn(['extraData1' => ['extraData1' => true]]);

        $normalizer2
            ->normalize($product, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX, [])
            ->willReturn(['extraData2' => ['extraData2' => false]]);

        $this->normalize($product, ProductModelNormalizer::INDEXING_FORMAT_PRODUCT_AND_MODEL_INDEX)->shouldReturn(
            [
                'id'                      => 'product_67',
                'identifier'              => 'sku-001',
                'created'                 => $now->format('c'),
                'updated'                 => $now->format('c'),
                'family'                  => null,
                'enabled'                 => false,
                'categories'              => [],
                'categories_of_ancestors' => [],
                'groups'                  => [],
                'completeness'            => ['normalized_completeness'],
                'family_variant'          => null,
                'parent'                  => null,
                'values'                  => [],
                'ancestors'               => [
                    'ids'   => [],
                    'codes' => [],
                    'labels' => [],
                ],
                'label'                   => [],
                'extraData1'              => ['extraData1' => true],
                'extraData2'              => ['extraData2' => false],
            ]
        );
    }

    public function it_fails_if_an_extra_normalizer_is_not_a_normalizer(
        $channelRepository,
        $localeRepository,
        $getProductCompletenesses
    ) {
        $this->beConstructedWith(
            $channelRepository,
            $localeRepository,
            $getProductCompletenesses,
            [new \stdClass()]
        );
        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }
}
