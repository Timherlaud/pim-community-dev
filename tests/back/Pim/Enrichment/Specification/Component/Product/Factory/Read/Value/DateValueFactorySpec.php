<?php
declare(strict_types=1);

namespace Specification\Akeneo\Pim\Enrichment\Component\Product\Factory\Read\Value;

use Akeneo\Pim\Enrichment\Component\Product\Factory\Read\Value\DateValueFactory;
use Akeneo\Pim\Enrichment\Component\Product\Factory\Read\Value\ReadValueFactory;
use Akeneo\Pim\Enrichment\Component\Product\Value\DateValue;
use Akeneo\Pim\Enrichment\Component\Product\Value\ScalarValue;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Pim\Structure\Component\Query\PublicApi\AttributeType\Attribute;
use Akeneo\Tool\Component\StorageUtils\Exception\InvalidPropertyTypeException;
use PhpSpec\ObjectBehavior;

/**
 * @author    Anael Chardan <anael.chardan@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class DateValueFactorySpec extends ObjectBehavior
{
    public function it_is_a_read_value_factory()
    {
        $this->shouldBeAnInstanceOf(ReadValueFactory::class);
    }

    public function it_supports_date_attribute_types()
    {
        $this->supportedAttributeType()->shouldReturn(AttributeTypes::DATE);
    }

    public function it_creates_a_localizable_and_scopable_value()
    {
        $attribute = $this->getAttribute(true, true);
        /** @var ScalarValue $value */
        $value = $this->createByCheckingData($attribute, 'ecommerce', 'fr_FR', '2019-05-21 07:29:04');
        $value->shouldBeLike(DateValue::scopableLocalizableValue('an_attribute', new \DateTime('2019-05-21 07:29:04'), 'ecommerce', 'fr_FR'));
    }

    public function it_creates_a_localizable_value()
    {
        $attribute = $this->getAttribute(true, false);
        /** @var ScalarValue $value */
        $value = $this->createByCheckingData($attribute, null, 'fr_FR', '2019-05-21 07:29:04');
        $value->shouldBeLike(DateValue::localizableValue('an_attribute', new \DateTime('2019-05-21 07:29:04'), 'fr_FR'));
    }

    public function it_creates_a_scopable_value()
    {
        $attribute = $this->getAttribute(false, true);
        /** @var ScalarValue $value */
        $value = $this->createByCheckingData($attribute, 'ecommerce', null, '2019-05-21 07:29:04');
        $value->shouldBeLike(DateValue::scopableValue('an_attribute', new \DateTime('2019-05-21 07:29:04'), 'ecommerce'));
    }

    public function it_creates_a_non_localizable_and_non_scopable_value()
    {
        $attribute = $this->getAttribute(false, false);
        $value = $this->createByCheckingData($attribute, null, null, '2019-05-21 07:29:04');
        $value->shouldBeLike(DateValue::value('an_attribute', new \DateTime('2019-05-21 07:29:04')));
    }

    public function it_creates_a_value_without_checking_type()
    {
        $attribute = $this->getAttribute(false, false);
        $value = $this->createWithoutCheckingData($attribute, null, null, '2019-05-21 07:29:04');
        $value->shouldBeLike(DateValue::value('an_attribute', new \DateTime('2019-05-21 07:29:04')));
    }

    public function it_throws_an_exception_when_provided_data_is_not_a_string()
    {
        $attribute = $this->getAttribute(false, false);

        $exception = InvalidPropertyTypeException::stringExpected(
            'an_attribute',
            DateValueFactory::class,
            []
        );

        $this
            ->shouldThrow($exception)
            ->during('createByCheckingData', [$attribute, 'ecommerce', 'en_US', []]);
    }

    private function getAttribute(bool $isLocalizable, bool $isScopable): Attribute
    {
        return new Attribute('an_attribute', AttributeTypes::DATE, [], $isLocalizable, $isScopable, null, false);
    }
}