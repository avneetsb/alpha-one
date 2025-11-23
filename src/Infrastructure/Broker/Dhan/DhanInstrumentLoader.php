<?php

namespace TradingPlatform\Infrastructure\Broker\Dhan;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;
use TradingPlatform\Domain\Instrument\Instrument;
use TradingPlatform\Infrastructure\Logger\LoggerService;

/**
 * Class: Dhan Instrument Loader
 *
 * Responsible for downloading, parsing, and persisting the instrument master list
 * from Dhan's daily CSV file. Handles large file processing and data mapping.
 */
class DhanInstrumentLoader
{
    private Client $httpClient;

    private string $csvUrl;

    private \Monolog\Logger $logger;

    /**
     * DhanInstrumentLoader constructor.
     */
    public function __construct()
    {
        $this->httpClient = new Client(['timeout' => 30]);
        $this->csvUrl = env('DHAN_CSV_URL', 'https://images.dhan.co/api-data/api-scrip-master.csv');
        $this->logger = LoggerService::getInstance()->getLogger();
    }

    /**
     * Execute the instrument loading process.
     *
     * 1. Downloads the CSV file from Dhan's CDN.
     * 2. Parses the CSV content row by row.
     * 3. Maps raw data to the `Instrument` domain model.
     * 4. Persists valid instruments to the database.
     *
     * @return array The array of parsed instrument data.
     *
     * @throws \Exception If download, parsing, or saving fails.
     *
     * @example
     * ```php
     * $loader = new DhanInstrumentLoader();
     * $instruments = $loader->loadInstruments();
     * echo "Loaded " . count($instruments) . " instruments.";
     * ```
     */
    public function loadInstruments(): array
    {
        try {
            $this->logger->info('Starting instrument load from Dhan CSV', ['url' => $this->csvUrl]);

            // Download CSV content
            $csvContent = $this->downloadCsv();

            // Parse CSV
            $instruments = $this->parseCsv($csvContent);

            // Validate and persist
            $savedCount = $this->saveInstruments($instruments);

            $this->logger->info('Instrument load completed', [
                'total_parsed' => count($instruments),
                'total_saved' => $savedCount,
            ]);

            return $instruments;

        } catch (\Exception $e) {
            $this->logger->error('Failed to load instruments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Download CSV content from URL.
     *
     * @return string CSV content.
     *
     * @throws \RuntimeException If download fails.
     */
    private function downloadCsv(): string
    {
        try {
            $response = $this->httpClient->get($this->csvUrl);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException("Failed to download CSV: HTTP {$response->getStatusCode()}");
            }

            return (string) $response->getBody();

        } catch (GuzzleException $e) {
            throw new \RuntimeException("Failed to download instruments CSV: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Parse CSV content into array of instruments.
     *
     * @throws \RuntimeException If parsing fails.
     */
    private function parseCsv(string $csvContent): array
    {
        try {
            $csv = Reader::createFromString($csvContent);
            $csv->setHeaderOffset(0); // First row is header

            $instruments = [];
            $lineNumber = 1;

            foreach ($csv as $record) {
                $lineNumber++;

                try {
                    $instrument = $this->parseRecord($record);
                    if ($instrument) {
                        $instruments[] = $instrument;
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Skipped invalid instrument record', [
                        'line' => $lineNumber,
                        'error' => $e->getMessage(),
                        'record' => $record,
                    ]);

                    continue;
                }
            }

            return $instruments;

        } catch (CsvException $e) {
            throw new \RuntimeException("Failed to parse CSV: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Parse a single CSV record.
     *
     * @return array|null Parsed data or null if invalid.
     */
    private function parseRecord(array $record): ?array
    {
        // Expected CSV columns from Dhan:
        // SEM_SMST_SECURITY_ID, SEM_INSTRUMENT_NAME, SEM_TRADING_SYMBOL, etc.

        // Validate required fields
        if (empty($record['SEM_SMST_SECURITY_ID']) || empty($record['SEM_TRADING_SYMBOL'])) {
            throw new \InvalidArgumentException('Missing required fields: security_id or symbol');
        }

        $securityId = trim($record['SEM_SMST_SECURITY_ID']);
        $symbol = trim($record['SEM_TRADING_SYMBOL']);
        $name = trim($record['SEM_INSTRUMENT_NAME'] ?? $symbol);
        $exchange = $this->determineExchange($record);
        $instrumentType = $this->determineInstrumentType($record);
        $segment = trim($record['SEM_SEGMENT'] ?? 'UNKNOWN');

        // Additional fields
        $lotSize = (int) ($record['SEM_LOT_UNITS'] ?? 1);
        $tickSize = (float) ($record['SEM_TICK_SIZE'] ?? 0.05);

        // Expiry for derivatives
        $expiry = null;
        if (isset($record['SEM_EXPIRY_DATE']) && ! empty($record['SEM_EXPIRY_DATE'])) {
            $expiry = $this->parseDate($record['SEM_EXPIRY_DATE']);
        }

        // Strike price for options
        $strikePrice = isset($record['SEM_STRIKE_PRICE']) ? (float) $record['SEM_STRIKE_PRICE'] : null;

        // Option type
        $optionType = null;
        if (isset($record['SEM_OPTION_TYPE']) && ! empty($record['SEM_OPTION_TYPE'])) {
            $optionType = strtoupper(trim($record['SEM_OPTION_TYPE']));
        }

        return [
            'broker_instrument_id' => $securityId,
            'symbol' => $symbol,
            'name' => $name,
            'exchange' => $exchange,
            'segment' => $segment,
            'instrument_type' => $instrumentType,
            'lot_size' => $lotSize,
            'tick_size' => $tickSize,
            'expiry' => $expiry,
            'strike_price' => $strikePrice,
            'option_type' => $optionType,
        ];
    }

    /**
     * Determine exchange from record.
     */
    private function determineExchange(array $record): string
    {
        $exchange = strtoupper(trim($record['SEM_EXM_EXCH_ID'] ?? ''));

        // Map Dhan exchange IDs to standard exchange codes
        $mapping = [
            'NSE' => 'NSE',
            'BSE' => 'BSE',
            'MCX' => 'MCX',
            'NFO' => 'NSE', // NSE F&O
            'BFO' => 'BSE', // BSE F&O
            'CDS' => 'NSE', // Currency Derivatives
        ];

        return $mapping[$exchange] ?? $exchange;
    }

    /**
     * Determine instrument type from record.
     */
    private function determineInstrumentType(array $record): string
    {
        $series = strtoupper(trim($record['SEM_SERIES'] ?? ''));
        $segment = strtoupper(trim($record['SEM_SEGMENT'] ?? ''));

        // Determine instrument type based on series and segment
        if (str_contains($segment, 'FUT')) {
            return 'FUTURE';
        } elseif (str_contains($segment, 'OPT') || str_contains($series, 'OPT')) {
            return 'OPTION';
        } elseif ($series === 'EQ' || $segment === 'EQUITY') {
            return 'EQUITY';
        } elseif (str_contains($segment, 'CURRENCY')) {
            return 'CURRENCY';
        } elseif (str_contains($segment, 'COMMODITY')) {
            return 'COMMODITY';
        }

        return 'UNKNOWN';
    }

    /**
     * Parse date string.
     *
     * @return string|null Y-m-d format or null.
     */
    private function parseDate(string $dateStr): ?string
    {
        try {
            // Dhan typically uses DD-MMM-YYYY format (e.g., "29-NOV-2024")
            $date = \DateTime::createFromFormat('d-M-Y', $dateStr);
            if ($date === false) {
                // Try alternate format
                $date = \DateTime::createFromFormat('Y-m-d', $dateStr);
            }

            return $date ? $date->format('Y-m-d') : null;
        } catch (\Exception $e) {
            $this->logger->warning('Failed to parse date', ['date' => $dateStr, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Save parsed instruments to database.
     *
     * @return int Count of saved instruments.
     */
    private function saveInstruments(array $instruments): int
    {
        $savedCount = 0;

        foreach ($instruments as $instrumentData) {
            try {
                Instrument::updateOrCreate(
                    ['broker_instrument_id' => $instrumentData['broker_instrument_id']],
                    $instrumentData
                );
                $savedCount++;
            } catch (\Exception $e) {
                $this->logger->error('Failed to save instrument', [
                    'instrument' => $instrumentData,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $savedCount;
    }
}
