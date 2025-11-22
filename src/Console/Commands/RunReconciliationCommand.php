<?php

namespace TradingPlatform\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Application\Services\ReconciliationService;

class RunReconciliationCommand extends Command
{
    protected static $defaultName = 'recon:run';
    private ReconciliationService $service;

    public function __construct(ReconciliationService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Run reconciliation process')
            ->addOption('broker', 'b', InputOption::VALUE_REQUIRED, 'Broker ID', 'dhan')
            ->addOption('scope', 's', InputOption::VALUE_REQUIRED, 'Scope (orders, positions, holdings, all)', 'all');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $brokerId = $input->getOption('broker');
        $scope = $input->getOption('scope');

        $output->writeln("<info>Starting reconciliation for {$brokerId} (Scope: {$scope})...</info>");

        $run = $this->service->startRun($brokerId, $scope);

        try {
            if ($scope === 'all' || $scope === 'orders') {
                $output->writeln("Reconciling orders...");
                $this->service->reconcileOrders($run);
            }
            
            if ($scope === 'all' || $scope === 'positions') {
                $output->writeln("Reconciling positions...");
                $this->service->reconcilePositions($run);
            }

            if ($scope === 'all' || $scope === 'holdings') {
                $output->writeln("Reconciling holdings...");
                $this->service->reconcileHoldings($run);
            }

            $this->service->completeRun($run);
            
            $output->writeln("<info>Reconciliation completed.</info>");
            $output->writeln("Processed: {$run->items_processed}");
            $output->writeln("Mismatches: {$run->mismatches_found}");
            
            if ($run->mismatches_found > 0) {
                return Command::FAILURE;
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln("<error>Reconciliation failed: {$e->getMessage()}</error>");
            $run->update(['status' => 'failed']);
            return Command::FAILURE;
        }
    }
}
