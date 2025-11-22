<?php

namespace TradingPlatform\Application\Commands\Order;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Infrastructure\Broker\Dhan\DhanOrderAdapter;

class CancelOrderCommand extends Command
{
    protected static $defaultName = 'cli:order:cancel';

    protected function configure()
    {
        $this->setDescription('Cancel an order')
            ->addOption('order-id', null, InputOption::VALUE_REQUIRED, 'Internal Order ID')
            ->addOption('broker', null, InputOption::VALUE_REQUIRED, 'Broker name', 'dhan')
            ->setHelp(
                "Usage:\n" .
                "  php bin/console cli:order:cancel --order-id=123 --broker=dhan\n\n" .
                "Options:\n" .
                "  --order-id   Required. Internal Order ID to cancel.\n" .
                "  --broker     Broker identifier. Default: dhan.\n\n" .
                "Behavior:\n" .
                "  Looks up local Order, sends cancel to broker, and updates status optimistically.\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orderId = $input->getOption('order-id');
        $broker = $input->getOption('broker');

        $order = Order::find($orderId);
        if (!$order) {
            $output->writeln("<error>Order not found: $orderId</error>");
            return Command::FAILURE;
        }

        if ($broker === 'dhan') {
            try {
                $adapter = new DhanOrderAdapter(env('DHAN_ACCESS_TOKEN'));
                $result = $adapter->cancelOrder($order->broker_order_id);
                
                $order->update(['status' => 'CANCELLED']); // Optimistic update
                $output->writeln("Order cancelled.");
            } catch (\Exception $e) {
                $output->writeln("<error>Failed to cancel order: " . $e->getMessage() . "</error>");
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
