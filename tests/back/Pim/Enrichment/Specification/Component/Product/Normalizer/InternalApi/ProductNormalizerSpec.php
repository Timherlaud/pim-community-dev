<?php

namespace Specification\Akeneo\Pim\Enrichment\Component\Product\Normalizer\InternalApi;

use Akeneo\Channel\Component\Repository\ChannelRepositoryInterface;
use Akeneo\Channel\Component\Repository\LocaleRepositoryInterface;
use Akeneo\Pim\Enrichment\Bundle\Context\CatalogContext;
use Akeneo\Pim\Enrichment\Bundle\Filter\CollectionFilterInterface;
use Akeneo\Pim\Enrichment\Component\Category\Query\AscendantCategoriesInterface;
use Akeneo\Pim\Enrichment\Component\Product\Association\MissingAssociationAdder;
use Akeneo\Pim\Enrichment\Component\Product\Converter\ConverterInterface;
use Akeneo\Pim\Enrichment\Component\Product\EntityWithFamilyVariant\EntityWithFamilyVariantAttributesProvider;
use Akeneo\Pim\Enrichment\Component\Product\Localization\Localizer\AttributeConverterInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\AssociationInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\GroupInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\Projection\ProductCompletenessCollection;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Enrichment\Component\Product\Normalizer\InternalApi\ImageNormalizer;
use Akeneo\Pim\Enrichment\Component\Product\Normalizer\InternalApi\ProductCompletenessCollectionNormalizer;
use Akeneo\Pim\Enrichment\Component\Product\Normalizer\InternalApi\VariantNavigationNormalizer;
use Akeneo\Pim\Enrichment\Component\Product\Query\GetProductCompletenesses;
use Akeneo\Pim\Enrichment\Component\Product\ValuesFiller\EntityWithFamilyValuesFillerInterface;
use Akeneo\Pim\Structure\Component\Model\AssociationTypeInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Structure\Component\Model\FamilyVariantInterface;
use Akeneo\Platform\Bundle\UIBundle\Provider\Form\FormProviderInterface;
use Akeneo\Platform\Bundle\UIBundle\Provider\StructureVersion\StructureVersionProviderInterface;
use Akeneo\Tool\Bundle\VersioningBundle\Manager\VersionManager;
use Akeneo\UserManagement\Bundle\Context\UserContext;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ProductNormalizerSpec extends ObjectBehavior
{
    function let(
        NormalizerInterface $normalizer,
        NormalizerInterface $versionNormalizer,
        VersionManager $versionManager,
        ImageNormalizer $imageNormalizer,
        LocaleRepositoryInterface $localeRepository,
        StructureVersionProviderInterface $structureVersionProvider,
        FormProviderInterface $formProvider,
        AttributeConverterInterface $localizedConverter,
        ConverterInterface $productValueConverter,
        ObjectManager $productManager,
        ChannelRepositoryInterface $channelRepository,
        CollectionFilterInterface $collectionFilter,
        ProductCompletenessCollectionNormalizer $completenessCollectionNormalizer,
        UserContext $userContext,
        EntityWithFamilyValuesFillerInterface $productValuesFiller,
        EntityWithFamilyVariantAttributesProvider $attributesProvider,
        VariantNavigationNormalizer $navigationNormalizer,
        AscendantCategoriesInterface $ascendantCategories,
        NormalizerInterface $incompleteValuesNormalizer,
        MissingAssociationAdder $missingAssociationAdder,
        NormalizerInterface $parentAssociationsNormalizer,
        CatalogContext $catalogContext,
        GetProductCompletenesses $getProductCompletenesses
    ) {
        $this->beConstructedWith(
            $normalizer,
            $versionNormalizer,
            $versionManager,
            $imageNormalizer,
            $localeRepository,
            $structureVersionProvider,
            $formProvider,
            $localizedConverter,
            $productValueConverter,
            $productManager,
            $channelRepository,
            $collectionFilter,
            $completenessCollectionNormalizer,
            $userContext,
            $productValuesFiller,
            $attributesProvider,
            $navigationNormalizer,
            $ascendantCategories,
            $incompleteValuesNormalizer,
            $missingAssociationAdder,
            $parentAssociationsNormalizer,
            $catalogContext,
            $getProductCompletenesses
        );
    }

    function it_supports_products(ProductInterface $mug)
    {
        $this->supportsNormalization($mug, 'internal_api')->shouldReturn(true);
    }

    function it_normalizes_products(
        $normalizer,
        $versionNormalizer,
        $versionManager,
        $imageNormalizer,
        $localeRepository,
        $structureVersionProvider,
        $formProvider,
        $localizedConverter,
        $productValueConverter,
        $channelRepository,
        $userContext,
        $collectionFilter,
        $productValuesFiller,
        $incompleteValuesNormalizer,
        $missingAssociationAdder,
        $getProductCompletenesses,
        $completenessCollectionNormalizer,
        ProductInterface $mug,
        AssociationInterface $upsell,
        AssociationTypeInterface $groupType,
        GroupInterface $group,
        ArrayCollection $groups,
        ValueInterface $image
    ) {
        $options = [
            'decimal_separator' => ',',
            'date_format'       => 'dd/MM/yyyy',
            'locale'            => 'en_US',
            'channel'           => 'mobile',
        ];

        $productNormalized = [
            'enabled'    => true,
            'categories' => ['kitchen'],
            'family'     => '',
            'values' => [
                'normalized_property' => [['data' => 'a nice normalized property', 'locale' => null, 'scope' => null]],
                'number'              => [['data' => 12.5000, 'locale' => null, 'scope' => null]],
                'metric'              => [['data' => 12.5000, 'locale' => null, 'scope' => null]],
                'prices'              => [['data' => 12.5, 'locale' => null, 'scope' => null]],
                'date'                => [['data' => '2015-01-31', 'locale' => null, 'scope' => null]],
                'picture'             => [['data' => 'a/b/c/my_picture.jpg', 'locale' => null, 'scope' => null]]
            ]
        ];

        $valuesLocalized = [
            'normalized_property' => [['data' => 'a nice normalized property', 'locale' => null, 'scope' => null]],
            'number'              => [['data' => '12,5000', 'locale' => null, 'scope' => null]],
            'metric'              => [['data' => '12,5000', 'locale' => null, 'scope' => null]],
            'prices'              => [['data' => '12,5', 'locale' => null, 'scope' => null]],
            'date'                => [['data' => '31/01/2015', 'locale' => null, 'scope' => null]],
            'picture'             => [['data' => 'a/b/c/my_picture.jpg', 'locale' => null, 'scope' => null]]
        ];

        $userContext->getUserTimezone()->willReturn('Pacific/Kiritimati');
        $normalizer->normalize($mug, 'standard', $options)->willReturn($productNormalized);
        $localizedConverter->convertToLocalizedFormats($productNormalized['values'], $options)->willReturn($valuesLocalized);

        $valuesConverted = $valuesLocalized;
        $valuesConverted['picture'] = [
            [
                'data' => [
                    'filePath' => 'a/b/c/my_picture.jpg', 'originalFilename' => 'my_picture.jpg'
                ],
                'locale' => null,
                'scope' => null
            ]
        ];

        $channelRepository->getFullChannels()->willReturn([]);
        $userContext->getUserLocales()->willReturn([]);
        $collectionFilter->filterCollection([], 'pim.internal_api.locale.view')->willReturn([]);

        $productValueConverter->convert($valuesLocalized)->willReturn($valuesConverted);

        $mug->isVariant()->willReturn(false);
        $mug->getId()->willReturn(12);
        $versionManager->getOldestLogEntry($mug)->willReturn('create_version');
        $versionNormalizer->normalize('create_version', 'internal_api', ['timezone' => 'Pacific/Kiritimati'])
            ->willReturn('normalized_create_version');
        $versionManager->getNewestLogEntry($mug)->willReturn('update_version');
        $versionNormalizer->normalize('update_version', 'internal_api', ['timezone' => 'Pacific/Kiritimati'])
            ->willReturn('normalized_update_version');

        $localeRepository->getActivatedLocaleCodes()->willReturn(['en_US', 'fr_FR']);
        $mug->getLabel('en_US', 'mobile')->willReturn('A nice Mug!');
        $mug->getLabel('fr_FR', 'mobile')->willReturn('Un très beau Mug !');
        $mug->getImage()->willReturn($image);
        $imageNormalizer->normalize($image, Argument::any())->willReturn([
            'filePath'         => '/p/i/m/4/all.png',
            'originalFileName' => 'all.png',
        ]);

        $mug->getAssociations()->willReturn([$upsell]);
        $upsell->getAssociationType()->willReturn($groupType);
        $groupType->getCode()->willReturn('group');
        $upsell->getGroups()->willReturn($groups);
        $groups->toArray()->willReturn([$group]);
        $group->getId()->willReturn(12);

        $productCompletenessCollection = new ProductCompletenessCollection(12, []);
        $getProductCompletenesses->fromProductId(12)->willReturn($productCompletenessCollection);
        $completenessCollectionNormalizer->normalize($productCompletenessCollection)->willReturn(['normalizedCompleteness']);

        $structureVersionProvider->getStructureVersion()->willReturn(12);
        $formProvider->getForm($mug)->willReturn('product-edit-form');

        $missingAssociationAdder->addMissingAssociations($mug)->shouldBeCalled();
        $productValuesFiller->fillMissingValues($mug)->shouldBeCalled();

        $incompleteValuesNormalizer->normalize($mug)->willReturn('INCOMPLETE VALUES');

        $this->normalize($mug, 'internal_api', $options)->shouldReturn(
            [
                'enabled'    => true,
                'categories' => ['kitchen'],
                'family'     => '',
                'values'     => $valuesConverted,
                'parent_associations' => null,
                'meta'       => [
                    'form'              => 'product-edit-form',
                    'id'                => 12,
                    'created'           => 'normalized_create_version',
                    'updated'           => 'normalized_update_version',
                    'model_type'        => 'product',
                    'structure_version' => 12,
                    'completenesses'    => ['normalizedCompleteness'],
                    'required_missing_attributes' => 'INCOMPLETE VALUES',
                    'image'             => [
                        'filePath'         => '/p/i/m/4/all.png',
                        'originalFileName' => 'all.png',
                    ],
                    'label'             => [
                        'en_US' => 'A nice Mug!',
                        'fr_FR' => 'Un très beau Mug !'
                    ],
                    'associations' => [
                        'group' => [
                            'groupIds' => [12]
                        ]
                    ],
                    'ascendant_category_ids'    => [],
                    'variant_navigation'        => [],
                    'attributes_for_this_level' => [],
                    'attributes_axes'           => [],
                    'parent_attributes'         => [],
                    'family_variant'            => null,
                    'level'                     => null,
                ]
            ]
        );
    }

    function it_normalizes_variant_products(
        $normalizer,
        $versionNormalizer,
        $versionManager,
        $imageNormalizer,
        $localeRepository,
        $structureVersionProvider,
        $formProvider,
        $localizedConverter,
        $productValueConverter,
        $channelRepository,
        $userContext,
        $collectionFilter,
        $productValuesFiller,
        $navigationNormalizer,
        $attributesProvider,
        $ascendantCategories,
        $incompleteValuesNormalizer,
        $missingAssociationAdder,
        $getProductCompletenesses,
        $completenessCollectionNormalizer,
        ProductInterface $mug,
        AssociationInterface $upsell,
        AssociationTypeInterface $groupType,
        GroupInterface $group,
        ArrayCollection $groups,
        ValueInterface $image,
        FamilyVariantInterface $familyVariant,
        AttributeInterface $color,
        AttributeInterface $size,
        AttributeInterface $description,
        ProductModelInterface $productModel
    ) {
        $options = [
            'decimal_separator' => ',',
            'date_format'       => 'dd/MM/yyyy',
            'locale'            => 'en_US',
            'channel'           => 'mobile',
        ];

        $productNormalized = [
            'enabled'    => true,
            'categories' => ['kitchen'],
            'family'     => '',
            'values' => [
                'normalized_property' => [['data' => 'a nice normalized property', 'locale' => null, 'scope' => null]],
                'number'              => [['data' => 12.5000, 'locale' => null, 'scope' => null]],
                'metric'              => [['data' => 12.5000, 'locale' => null, 'scope' => null]],
                'prices'              => [['data' => 12.5, 'locale' => null, 'scope' => null]],
                'date'                => [['data' => '2015-01-31', 'locale' => null, 'scope' => null]],
                'picture'             => [['data' => 'a/b/c/my_picture.jpg', 'locale' => null, 'scope' => null]]
            ]
        ];

        $valuesLocalized = [
            'normalized_property' => [['data' => 'a nice normalized property', 'locale' => null, 'scope' => null]],
            'number'              => [['data' => '12,5000', 'locale' => null, 'scope' => null]],
            'metric'              => [['data' => '12,5000', 'locale' => null, 'scope' => null]],
            'prices'              => [['data' => '12,5', 'locale' => null, 'scope' => null]],
            'date'                => [['data' => '31/01/2015', 'locale' => null, 'scope' => null]],
            'picture'             => [['data' => 'a/b/c/my_picture.jpg', 'locale' => null, 'scope' => null]]
        ];

        $mug->isVariant()->willReturn(true);
        $userContext->getUserTimezone()->willReturn('Pacific/Kiritimati');
        $normalizer->normalize($mug, 'standard', $options)->willReturn($productNormalized);
        $localizedConverter->convertToLocalizedFormats($productNormalized['values'], $options)->willReturn($valuesLocalized);

        $valuesConverted = $valuesLocalized;
        $valuesConverted['picture'] = [
            [
                'data' => [
                    'filePath' => 'a/b/c/my_picture.jpg', 'originalFilename' => 'my_picture.jpg'
                ],
                'locale' => null,
                'scope' => null
            ]
        ];

        $channelRepository->getFullChannels()->willReturn([]);
        $userContext->getUserLocales()->willReturn([]);
        $collectionFilter->filterCollection([], 'pim.internal_api.locale.view')->willReturn([]);

        $productValueConverter->convert($valuesLocalized)->willReturn($valuesConverted);

        $mug->getId()->willReturn(12);
        $versionManager->getOldestLogEntry($mug)->willReturn('create_version');
        $versionNormalizer->normalize('create_version', 'internal_api', ['timezone' => 'Pacific/Kiritimati'])
            ->willReturn('normalized_create_version');
        $versionManager->getNewestLogEntry($mug)->willReturn('update_version');
        $versionNormalizer->normalize('update_version', 'internal_api', ['timezone' => 'Pacific/Kiritimati'])
            ->willReturn('normalized_update_version');

        $localeRepository->getActivatedLocaleCodes()->willReturn(['en_US', 'fr_FR']);
        $mug->getLabel('en_US', 'mobile')->willReturn('A nice Mug!');
        $mug->getLabel('fr_FR', 'mobile')->willReturn('Un très beau Mug !');
        $mug->getImage()->willReturn($image);
        $imageNormalizer->normalize($image, Argument::any())->willReturn([
            'filePath'         => '/p/i/m/4/all.png',
            'originalFileName' => 'all.png',
        ]);

        $mug->getAssociations()->willReturn([$upsell]);
        $upsell->getAssociationType()->willReturn($groupType);
        $groupType->getCode()->willReturn('group');
        $upsell->getGroups()->willReturn($groups);
        $groups->toArray()->willReturn([$group]);
        $group->getId()->willReturn(12);

        $productCompletenessCollection = new ProductCompletenessCollection(12, []);
        $getProductCompletenesses->fromProductId(12)->willReturn($productCompletenessCollection);
        $completenessCollectionNormalizer->normalize($productCompletenessCollection)->willReturn(['normalizedCompletenesses']);

        $structureVersionProvider->getStructureVersion()->willReturn(12);
        $formProvider->getForm($mug)->willReturn('product-edit-form');

        $missingAssociationAdder->addMissingAssociations($mug)->shouldBeCalled();
        $productValuesFiller->fillMissingValues($mug)->shouldBeCalled();

        $navigationNormalizer->normalize($mug, 'internal_api', $options)
            ->willReturn(['NAVIGATION NORMALIZED']);

        $mug->getFamilyVariant()->willReturn($familyVariant);
        $normalizer->normalize($familyVariant, 'standard')->willReturn([
            'NORMALIZED FAMILY'
        ]);

        $mug->getParent()->willReturn($productModel);
        $productModel->getId()->willReturn(42);
        $attributesProvider->getAttributes($mug)->willReturn([$size]);
        $attributesProvider->getAxes($mug)->willReturn([$size]);
        $attributesProvider->getAxes($productModel)->willReturn([]);
        $attributesProvider->getAttributes($productModel)->willReturn([$color, $description]);

        $mug->getVariationLevel()->willReturn(1);

        $color->getCode()->willReturn('color');
        $size->getCode()->willReturn('size');
        $description->getCode()->willReturn('description');

        $ascendantCategories->getCategoryIds($mug)->willReturn([42]);
        $incompleteValuesNormalizer->normalize($mug)->willReturn('INCOMPLETE VALUES');

        $this->normalize($mug, 'internal_api', $options)->shouldReturn(
            [
                'enabled'    => true,
                'categories' => ['kitchen'],
                'family'     => '',
                'values'     => $valuesConverted,
                'parent_associations' => null,
                'meta'       => [
                    'form'              => 'product-edit-form',
                    'id'                => 12,
                    'created'           => 'normalized_create_version',
                    'updated'           => 'normalized_update_version',
                    'model_type'        => 'product',
                    'structure_version' => 12,
                    'completenesses'    => ['normalizedCompletenesses'],
                    'required_missing_attributes' => 'INCOMPLETE VALUES',
                    'image'             => [
                        'filePath'         => '/p/i/m/4/all.png',
                        'originalFileName' => 'all.png',
                    ],
                    'label'             => [
                        'en_US' => 'A nice Mug!',
                        'fr_FR' => 'Un très beau Mug !'
                    ],
                    'associations' => [
                        'group' => [
                            'groupIds' => [12]
                        ]
                    ],
                    'ascendant_category_ids'    => [42],
                    'variant_navigation' => ['NAVIGATION NORMALIZED'],
                    'attributes_for_this_level' => ['size'],
                    'attributes_axes'           => ['size'],
                    'parent_attributes'         => ['color', 'description'],
                    'family_variant'            => ['NORMALIZED FAMILY'],
                    'level'                     => 1,
                ]
            ]
        );
    }
}
