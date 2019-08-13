<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Bundle\Product\Query\Sql\Completeness;

use Akeneo\Pim\Enrichment\Component\Product\Model\Projection\ProductCompleteness;
use Akeneo\Pim\Enrichment\Component\Product\Model\Projection\ProductCompletenessCollection;
use Akeneo\Pim\Enrichment\Component\Product\Query\SaveProductCompletenesses;
use Doctrine\DBAL\Connection;

/**
 * @author    Mathias METAYER <mathias.metayer@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class SqlSaveProductCompletenesses implements SaveProductCompletenesses
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function save(ProductCompletenessCollection $completenesses): void
    {
        $this->saveAll([$completenesses]);
    }

    /**
     * {@inheritdoc}
     */
    public function saveAll(array $productCompletenessCollections): void
    {
        $productIds = array_unique(array_map(function (ProductCompletenessCollection $productCompletenessCollection) {
            return $productCompletenessCollection->productId();
        }, $productCompletenessCollections));

        $sqlCompletenessValues = $this->productCompletenessCollectionsToSqlValues($productCompletenessCollections);

        $insertCompletenessSqlQuery = <<<SQL
INSERT INTO pim_catalog_completeness
    (locale_id, channel_id, product_id, missing_count, required_count)
VALUES
    %s
SQL;
        $insertCompletenessSqlQuery = sprintf($insertCompletenessSqlQuery, $sqlCompletenessValues);

        $this->connection->transactional(function (Connection $connection) use ($productIds, $insertCompletenessSqlQuery) {
            $connection->executeQuery('DELETE FROM pim_catalog_completeness WHERE product_id IN (:productIds)', $productIds);
            $connection->executeQuery($insertCompletenessSqlQuery);
        });
    }

    /**
     * Return an array of SQL values for pim_catalog_completeness
     *
     * @return array ['(locale_id, channel_id, product_id, missing_count, required_count)', ...]
     */
    private function productCompletenessCollectionsToSqlValues(array $productCompletenessCollections): array
    {
        $localeIdsByCodes = $this->getLocaleIsdByCodes();
        $channelIdsByCodes = $this->getChannelIdsByCodes();

        $completenessValues = array_map(function (ProductCompletenessCollection $productCompletenessCollection) use ($localeIdsByCodes, $channelIdsByCodes) {
            // return ['(locale_id, channel_id, product_id, missing_count, required_count)', ...];
            return array_map(function (ProductCompleteness $productCompleteness) use ($localeIdsByCodes, $channelIdsByCodes, $productCompletenessCollection) {
                return implode(',', [
                    $localeIdsByCodes[$productCompleteness->localeCode()],
                    $channelIdsByCodes[$productCompleteness->channelCode()],
                    $productCompletenessCollection->productId(),
                    count($productCompleteness->missingAttributeCodes()), //TODO TO REPLACE WITH THE COUNT METHOD
                    $productCompleteness->requiredCount()
                ]);
            }, iterator_to_array($productCompletenessCollection));
        }, $productCompletenessCollections);

        $completenessValues = array_merge(...$completenessValues);

        return implode(',', $completenessValues);
    }

    private function getLocaleIsdByCodes(): array
    {
        $query = <<<SQL
SELECT DISTINCT(locale.id) as locale_id, locale.code as locale_code
FROM pim_catalog_locale locale
INNER JOIN pim_catalog_channel_locale channel_locale ON locale.id = channel_locale.locale_id
SQL;
        $rows = $this->connection->fetchAll($query);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['locale_code']] = $row['locale_id'];
        }

        return $result;
    }

    private function getChannelIdsByCodes(): array
    {
        $query = 'SELECT channel.id as channel_id, channel.code as channel_code FROM pim_catalog_channel channel';
        $rows = $this->connection->fetchAll($query);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['channel_code']] = $row['channel_id'];
        }

        return $result;
    }
}
