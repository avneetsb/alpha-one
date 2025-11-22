<?php

namespace TradingPlatform\Application\Commands\Reconciliation;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Infrastructure\Broker\Dhan\DhanOrderAdapter;

class RunReconciliationCommand extends Command
{
    protected static $defaultName = 'cli:recon:run';

    protected function configure()
    {
        $this->setDescription('Reconcile local orders with broker')
            ->setHelp(
                "Usage:\n" .
                "  php bin/console cli:recon:run\n\n" .
                "Behavior:\n" .
                "  Compares local orders against broker data and updates statuses accordingly (mocked).\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("Starting Reconciliation...");
        
        // 1. Fetch all open orders locally
        $localOrders = Order::where('status', 'OPEN')->get();
        $output->writeln("Found " . $localOrders->count() . " open local orders.");
        
        // 2. Fetch open orders from Broker
        // In a real app, we'd use the adapter to fetch from broker
        // $brokerOrders = $this->adapter->getOrders();
        
        // Mock broker orders for demo
        $brokerOrders = []; 
        $output->writeln("Fetched " . count($brokerOrders) . " orders from Broker.");
        
        // 3. Compare and update
        foreach ($localOrders as $order) {
            // Check if order exists in broker list
            // If not found, mark as REJECTED or CANCELLED (depending on logic)
            // If found, update status if different
            
            $output->writeln("Checking Order ID: " . $order->id);
        }
        
        $output->writeln("Reconciliation Complete.");
        return Command::SUCCESS;
    }
}
