<?php

namespace TradingPlatform\Application\Commands\Order;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Domain\Instrument\Instrument;
use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Infrastructure\Broker\Dhan\DhanOrderAdapter;

/**
 * Command: Place Order
 *
 * Places a new buy or sell order. Creates a local order record and submits
 * it to the broker for execution. Supports LIMIT and MARKET orders.
 */
class PlaceOrderCommand extends Command
{
    protected static $defaultName = 'cli:order:place';

    /**
     * Configure the command.
     *
     * @return void
     */
    /**
     * Configure the command options.
     *
     * Defines options for instrument, quantity, price, side, type, and broker.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Place an order')
            ->addOption('instrument', null, InputOption::VALUE_REQUIRED, 'Instrument symbol')
            ->addOption('qty', null, InputOption::VALUE_REQUIRED, 'Quantity')
            ->addOption('price', null, InputOption::VALUE_REQUIRED, 'Price')
            ->addOption('side', null, InputOption::VALUE_REQUIRED, 'Side (BUY/SELL)', 'BUY')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Type (LIMIT/MARKET)', 'LIMIT')
            ->addOption('broker', null, InputOption::VALUE_REQUIRED, 'Broker name', 'dhan')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:order:place --instrument=RELIANCE --qty=100 --price=2500.50 --side=BUY --type=LIMIT --broker=dhan\n\n".
                "Options:\n".
                "  --instrument    Required. Trading symbol (e.g. RELIANCE).\n".
                "  --qty           Required. Quantity to trade.\n".
                "  --price         Required for LIMIT. Price per unit.\n".
                "  --side          BUY or SELL. Default: BUY.\n".
                "  --type          LIMIT or MARKET. Default: LIMIT.\n".
                "  --broker        Broker identifier (e.g. dhan). Default: dhan.\n\n".
                "Behavior:\n".
                "  Creates a local Order record and attempts placement via selected broker adapter.\n"
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
     * Execute the order placement.
     *
     * Validates the instrument, creates a local order with 'QUEUED' status,
     * and submits it to the broker. Updates status to 'PENDING' or 'REJECTED'
     * based on broker response.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example Place a LIMIT BUY order
     * ```bash
     * php bin/console cli:order:place --instrument=TCS --qty=10 --price=3500 --side=BUY --type=LIMIT
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symbol = $input->getOption('instrument');
        $qty = $input->getOption('qty');
        $price = $input->getOption('price');
        $side = $input->getOption('side');
        $type = $input->getOption('type');
        $broker = $input->getOption('broker');

        // Find instrument
        $instrument = Instrument::where('symbol', $symbol)->first();
        if (! $instrument) {
            $output->writeln("<error>Instrument not found: $symbol</error>");

            return Command::FAILURE;
        }

        // Create Order record
        $order = Order::create([
            'instrument_id' => $instrument->id,
            'side' => $side,
            'type' => $type,
            'validity' => 'DAY',
            'qty' => $qty,
            'price' => $price,
            'status' => 'QUEUED',
            'client_order_id' => uniqid('ord_'),
        ]);

        $output->writeln("Order created locally with ID: {$order->id}");

        // Execute on Broker
        if ($broker === 'dhan') {
            try {
                $adapter = new DhanOrderAdapter(env('DHAN_ACCESS_TOKEN'));
                $result = $adapter->placeOrder($order);

                $order->update([
                    'status' => 'PENDING', // Or map from result
                    'broker_order_id' => $result['orderId'] ?? null,
                ]);

                $output->writeln('Order placed on Dhan. Broker ID: '.($result['orderId'] ?? 'N/A'));
            } catch (\Exception $e) {
                $output->writeln('<error>Failed to place order: '.$e->getMessage().'</error>');
                $order->update(['status' => 'REJECTED']);

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
