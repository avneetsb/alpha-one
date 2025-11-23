<?php

namespace TradingPlatform\Application\Commands\Order;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Infrastructure\Broker\Dhan\DhanOrderAdapter;

/**
 * Command: Cancel Order
 *
 * Cancels an active order. Sends a cancellation request to the broker
 * and optimistically updates the local order status.
 */
class CancelOrderCommand extends Command
{
    protected static $defaultName = 'cli:order:cancel';

    /**
     * Configure the command.
     *
     * @return void
     */
    /**
     * Configure the command options.
     *
     * Requires the internal Order ID and optionally the broker identifier.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Cancel an order')
            ->addOption('order-id', null, InputOption::VALUE_REQUIRED, 'Internal Order ID')
            ->addOption('broker', null, InputOption::VALUE_REQUIRED, 'Broker name', 'dhan')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:order:cancel --order-id=123 --broker=dhan\n\n".
                "Options:\n".
                "  --order-id   Required. Internal Order ID to cancel.\n".
                "  --broker     Broker identifier. Default: dhan.\n\n".
                "Behavior:\n".
                "  Looks up local Order, sends cancel to broker, and updates status optimistically.\n"
            );
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     */
    /**
     * Execute the order cancellation.
     *
     * Retrieves the order, validates existence, and triggers the cancellation
     * via the broker adapter.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example Cancel order #123
     * ```bash
     * php bin/console cli:order:cancel --order-id=123
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orderId = $input->getOption('order-id');
        $broker = $input->getOption('broker');

        $order = Order::find($orderId);
        if (! $order) {
            $output->writeln("<error>Order not found: $orderId</error>");

            return Command::FAILURE;
        }

        if ($broker === 'dhan') {
            try {
                $adapter = new DhanOrderAdapter(env('DHAN_ACCESS_TOKEN'));
                $result = $adapter->cancelOrder($order->broker_order_id);

                $order->update(['status' => 'CANCELLED']); // Optimistic update
                $output->writeln('Order cancelled.');
            } catch (\Exception $e) {
                $output->writeln('<error>Failed to cancel order: '.$e->getMessage().'</error>');

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
