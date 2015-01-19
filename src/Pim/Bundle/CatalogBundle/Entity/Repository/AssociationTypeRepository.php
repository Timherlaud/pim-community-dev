<?php

namespace Pim\Bundle\CatalogBundle\Entity\Repository;

use Pim\Bundle\CatalogBundle\Doctrine\ReferableEntityRepository;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Repository\AssociationTypeRepositoryInterface;

/**
 * Association repository
 *
 * @author    Gildas Quemener <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @deprecated will be moved to Pim\Bundle\CatalogBundle\Doctrine\ORM\Repository in 1.4
 */
class AssociationTypeRepository extends ReferableEntityRepository implements AssociationTypeRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findMissingAssociationTypes(ProductInterface $product)
    {
        $qb = $this->createQueryBuilder('a');

        if ($associations = $product->getAssociations()) {
            $associationTypeIds = $associations->map(
                function ($association) {
                    return $association->getAssociationType()->getId();
                }
            );

            if (!$associationTypeIds->isEmpty()) {
                $qb->andWhere(
                    $qb->expr()->notIn('a.id', $associationTypeIds->toArray())
                );
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function createDatagridQueryBuilder()
    {
        $qb = $this->createQueryBuilder('a');
        $rootAlias = $qb->getRootAlias();

        $labelExpr = sprintf(
            "(CASE WHEN translation.label IS NULL THEN %s.code ELSE translation.label END)",
            $rootAlias
        );

        $qb
            ->addSelect($rootAlias)
            ->addSelect(sprintf("%s AS label", $labelExpr))
            ->addSelect('translation.label');

        $qb
            ->leftJoin($rootAlias .'.translations', 'translation', 'WITH', 'translation.locale = :localeCode');

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function countAll()
    {
        $qb = $this->createQueryBuilder('a');

        return (int) $qb
            ->select('count(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
