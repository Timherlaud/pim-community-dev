<?php

namespace Akeneo\Channel\Bundle\Controller\UI;

use Akeneo\Channel\Component\Exception\LinkedChannelException;
use Akeneo\Channel\Component\Model\Currency;
use Akeneo\Tool\Component\StorageUtils\Saver\SaverInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Currency controller for configuration
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CurrencyController
{
    /** @var RequestStack */
    private $requestStack;

    /** @var RouterInterface */
    private $router;

    /** @var SaverInterface */
    private $currencySaver;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        RequestStack $requestStack,
        RouterInterface $router,
        SaverInterface $currencySaver,
        TranslatorInterface $translator
    ) {
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->currencySaver = $currencySaver;
        $this->translator = $translator;
    }

    /**
     * Activate/Deactivate a currency
     *
     * @param Currency $currency
     *
     * @AclAncestor("pim_enrich_currency_toggle")
     *
     * @return JsonResponse
     */
    public function toggleAction(Currency $currency)
    {
        $request = $this->requestStack->getCurrentRequest();

        try {
            $currency->toggleActivation();
            $this->currencySaver->save($currency);

            $request
                ->getSession()
                ->getFlashBag()
                ->add('success', $this->translator->trans('flash.currency.updated'));
        } catch (LinkedChannelException $e) {
            $request
                ->getSession()
                ->getFlashBag()
                ->add('error', $this->translator->trans('flash.currency.error.linked_to_channel'));
        } catch (\Exception $e) {
            $request
                ->getSession()
                ->getFlashBag()
                ->add('error', $this->translator->trans('flash.error ocurred'));
        }

        return new JsonResponse(['route' => 'pim_enrich_currency_index']);
    }
}
