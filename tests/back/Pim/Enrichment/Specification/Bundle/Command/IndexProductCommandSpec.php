<?php

namespace Specification\Akeneo\Pim\Enrichment\Bundle\Command;

use Akeneo\Tool\Bundle\ElasticsearchBundle\Refresh;
use Akeneo\Tool\Bundle\ElasticsearchBundle\Client;
use Akeneo\Tool\Component\StorageUtils\Indexer\BulkIndexerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ProductRepositoryInterface;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IndexProductCommandSpec extends ObjectBehavior
{
    function let(
        ContainerInterface $container,
        ProductRepositoryInterface $productRepository,
        BulkIndexerInterface $bulkProductIndexer,
        ObjectManager $objectManager,
        Client $productAndProductModelClient
    ) {
        $this->beConstructedWith(
            $productRepository,
            $bulkProductIndexer,
            $objectManager,
            $productAndProductModelClient,
            'akeneo_pim_product_and_product_model'
        );
        $this->setContainer($container);

        $productAndProductModelClient->hasIndex()->willReturn(true);
    }

    function it_has_a_name()
    {
        $this->getDefaultName()->shouldReturn('pim:product:index');
    }

    function it_is_a_command()
    {
        $this->shouldBeAnInstanceOf(ContainerAwareCommand::class);
    }

    function it_indexes_all_products(
        ProductRepositoryInterface $productRepository,
        BulkIndexerInterface $bulkProductIndexer,
        ObjectManager $objectManager,
        InputInterface $input,
        OutputInterface $output,
        Application $application,
        HelperSet $helperSet,
        InputDefinition $definition,
        ProductInterface $product1,
        ProductInterface $product2,
        ProductInterface $product3,
        ProductInterface $product4,
        ProductInterface $product5,
        OutputFormatter $formatter
    ) {
        $output->isDecorated()->willReturn(true);
        $output->getVerbosity()->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $output->getFormatter()->willReturn($formatter);

        $productRepository->countAll()->willReturn(5);
        $productRepository->searchAfter(null, 100)->willReturn([$product1, $product2]);
        $productRepository->searchAfter($product2, 100)->willReturn([$product3, $product4]);
        $productRepository->searchAfter($product4, 100)->willReturn([$product5]);
        $productRepository->searchAfter($product5, 100)->willReturn([]);

        $bulkProductIndexer->indexAll([$product1, $product2], ['index_refresh' => Refresh::disable()])->shouldBeCalled();
        $bulkProductIndexer->indexAll([$product3, $product4], ['index_refresh' => Refresh::disable()])->shouldBeCalled();
        $bulkProductIndexer->indexAll([$product5], ['index_refresh' => Refresh::disable()])->shouldBeCalled();

        $objectManager->clear()->shouldBeCalledTimes(3);

        $output->writeln('<info>5 products to index</info>')->shouldBeCalled();
        $output->write(Argument::any())->shouldBeCalled();
        $output->writeln('<info>5 products indexed</info>')->shouldBeCalled();

        $commandInput = new ArrayInput([
            'command'    => 'pim:product:index',
            '--no-debug' => true,
        ]);
        $application->run($commandInput, $output)->willReturn(0);

        $definition->getOptions()->willReturn([]);
        $definition->getArguments()->willReturn([]);

        $application->getHelperSet()->willReturn($helperSet);
        $application->getDefinition()->willReturn($definition);

        $this->setApplication($application);
        $input->bind(Argument::any())->shouldBeCalled();
        $input->isInteractive()->shouldBeCalled();
        $input->hasArgument(Argument::any())->shouldBeCalled();
        $input->validate()->shouldBeCalled();
        $input->getArgument('identifiers')->willReturn([]);
        $input->getOption('all')->willReturn(true);
        $this->run($input, $output);
    }

    function it_indexes_a_product_with_identifier(
        ProductRepositoryInterface $productRepository,
        BulkIndexerInterface $bulkProductIndexer,
        ObjectManager $objectManager,
        InputInterface $input,
        OutputInterface $output,
        Application $application,
        HelperSet $helperSet,
        InputDefinition $definition,
        ProductInterface $productToIndex,
        OutputFormatter $formatter
    ) {
        $output->isDecorated()->willReturn(true);
        $output->getVerbosity()->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $output->getFormatter()->willReturn($formatter);

        $productRepository->findBy(['identifier' => ['product_identifier_to_index']])->willReturn([$productToIndex]);

        $bulkProductIndexer->indexAll([$productToIndex], ['index_refresh' => Refresh::disable()])->shouldBeCalled();
        $objectManager->clear()->shouldBeCalled();

        $output->writeln('<info>1 products found for indexing</info>')->shouldBeCalled();
        $output->write(Argument::any())->shouldBeCalled();
        $output->writeln('<info>1 products indexed</info>')->shouldBeCalled();

        $commandInput = new ArrayInput([
            'command'       => 'pim:product:index',
            '--identifiers' => ['product_identifier_to_index'],
            '--no-debug'    => true,
        ]);
        $application->run($commandInput, $output)->willReturn(0);

        $definition->getOptions()->willReturn([]);
        $definition->getArguments()->willReturn([]);

        $application->getHelperSet()->willReturn($helperSet);
        $application->getDefinition()->willReturn($definition);

        $this->setApplication($application);
        $input->bind(Argument::any())->shouldBeCalled();
        $input->isInteractive()->shouldBeCalled();
        $input->hasArgument(Argument::any())->shouldBeCalled();
        $input->validate()->shouldBeCalled();
        $input->getArgument('identifiers')->willReturn(['product_identifier_to_index']);
        $input->getOption('all')->willReturn(false);
        $this->run($input, $output);
    }

    function it_indexes_multiple_products_with_identifiers(
        ProductRepositoryInterface $productRepository,
        BulkIndexerInterface $bulkProductIndexer,
        ObjectManager $objectManager,
        InputInterface $input,
        OutputInterface $output,
        Application $application,
        HelperSet $helperSet,
        InputDefinition $definition,
        ProductInterface $product1,
        ProductInterface $product2,
        OutputFormatter $formatter
    ) {
        $output->isDecorated()->willReturn(true);
        $output->getVerbosity()->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $output->getFormatter()->willReturn($formatter);

        $productRepository->findBy(['identifier' => ['product_1', 'product_2']])->willReturn([$product1, $product2]);

        $bulkProductIndexer->indexAll([$product1, $product2], ['index_refresh' => Refresh::disable()])->shouldBeCalled();
        $objectManager->clear()->shouldBeCalled();

        $output->writeln('<info>2 products found for indexing</info>')->shouldBeCalled();
        $output->write(Argument::any())->shouldBeCalled();
        $output->writeln('<info>2 products indexed</info>')->shouldBeCalled();

        $commandInput = new ArrayInput([
            'command'       => 'pim:product:index',
            '--identifiers' => ['product_1', 'product_2'],
            '--no-debug'    => true,
        ]);
        $application->run($commandInput, $output)->willReturn(0);

        $definition->getOptions()->willReturn([]);
        $definition->getArguments()->willReturn([]);

        $application->getHelperSet()->willReturn($helperSet);
        $application->getDefinition()->willReturn($definition);

        $this->setApplication($application);
        $input->bind(Argument::any())->shouldBeCalled();
        $input->isInteractive()->shouldBeCalled();
        $input->hasArgument(Argument::any())->shouldBeCalled();
        $input->validate()->shouldBeCalled();
        $input->getArgument('identifiers')->willReturn(['product_1', 'product_2']);
        $input->getOption('all')->willReturn(false);
        $this->run($input, $output);
    }

    function it_does_not_index_non_existing_products(
        ProductRepositoryInterface $productRepository,
        BulkIndexerInterface $bulkProductIndexer,
        ObjectManager $objectManager,
        InputInterface $input,
        OutputInterface $output,
        Application $application,
        HelperSet $helperSet,
        InputDefinition $definition,
        ProductInterface $productToIndex,
        OutputFormatter $formatter
    ) {
        $output->isDecorated()->willReturn(true);
        $output->getVerbosity()->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $output->getFormatter()->willReturn($formatter);

        $productRepository->findBy(['identifier' => ['product_1', 'wrong_product']])->willReturn([$productToIndex]);

        $productToIndex->getIdentifier()->willReturn('product_1');

        $bulkProductIndexer->indexAll([$productToIndex], ['index_refresh' => Refresh::disable()])->shouldBeCalled();
        $objectManager->clear()->shouldBeCalled();

        $output->writeln('<error>Some products were not found for the given identifiers: wrong_product</error>')->shouldBeCalled();
        $output->writeln('<info>1 products found for indexing</info>')->shouldBeCalled();
        $output->write(Argument::any())->shouldBeCalled();
        $output->writeln('<info>1 products indexed</info>')->shouldBeCalled();

        $commandInput = new ArrayInput([
            'command'       => 'pim:product:index',
            '--identifiers' => ['product_1', 'wrong_product'],
            '--no-debug'    => true,
        ]);
        $application->run($commandInput, $output)->willReturn(0);

        $definition->getOptions()->willReturn([]);
        $definition->getArguments()->willReturn([]);

        $application->getHelperSet()->willReturn($helperSet);
        $application->getDefinition()->willReturn($definition);

        $this->setApplication($application);
        $input->bind(Argument::any())->shouldBeCalled();
        $input->isInteractive()->shouldBeCalled();
        $input->hasArgument(Argument::any())->shouldBeCalled();
        $input->validate()->shouldBeCalled();
        $input->getArgument('identifiers')->willReturn(['product_1', 'wrong_product']);
        $input->getOption('all')->willReturn(false);
        $this->run($input, $output);
    }

    function it_does_not_index_products_if_the_all_flag_is_not_set_and_no_identifier_is_passed(
        InputInterface $input,
        OutputInterface $output,
        Application $application,
        HelperSet $helperSet,
        InputDefinition $definition,
        OutputFormatter $formatter
    ) {
        $output->isDecorated()->willReturn(true);
        $output->getVerbosity()->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $output->getFormatter()->willReturn($formatter);

        $output->writeln('<error>Please specify a list of product identifiers to index or use the flag --all to index all products</error>')
            ->shouldBeCalled();

        $commandInput = new ArrayInput([
            'command'       => 'pim:product:index',
            '--identifiers' => [],
            '--all'         => false,
            '--no-debug'    => true,
        ]);
        $application->run($commandInput, $output)->willReturn(0);

        $definition->getOptions()->willReturn([]);
        $definition->getArguments()->willReturn([]);

        $application->getHelperSet()->willReturn($helperSet);
        $application->getDefinition()->willReturn($definition);

        $this->setApplication($application);
        $input->bind(Argument::any())->shouldBeCalled();
        $input->isInteractive()->shouldBeCalled();
        $input->hasArgument(Argument::any())->shouldBeCalled();
        $input->validate()->shouldBeCalled();
        $input->getArgument('identifiers')->willReturn([]);
        $input->getOption('all')->willReturn(false);
        $this->run($input, $output);
    }

    function it_throws_an_exception_when_the_product_and_product_model_index_does_not_exist(
        $productAndProductModelClient,
        Application $application,
        InputInterface $input,
        OutputInterface $output,
        HelperSet $helperSet,
        InputDefinition $definition,
        OutputFormatter $formatter
    ) {
        $output->isDecorated()->willReturn(true);
        $output->getVerbosity()->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $output->getFormatter()->willReturn($formatter);

        $productAndProductModelClient->hasIndex()->willReturn(false);

        $commandInput = new ArrayInput([
            'command'    => 'pim:product:index',
            '--no-debug' => true,
        ]);
        $application->run($commandInput, $output)->willReturn(0);

        $definition->getOptions()->willReturn([]);
        $definition->getArguments()->willReturn([]);

        $application->getHelperSet()->willReturn($helperSet);
        $application->getDefinition()->willReturn($definition);
        $this->setApplication($application);
        $input->bind(Argument::any())->shouldBeCalled();
        $input->isInteractive()->shouldBeCalled();
        $input->hasArgument(Argument::any())->shouldBeCalled();
        $input->validate()->shouldBeCalled();
        $input->getArgument('identifiers')->willReturn([]);
        $input->getOption('all')->willReturn(true);

        $this->shouldThrow(\RuntimeException::class)->during('run', [$input, $output]);
    }
}
