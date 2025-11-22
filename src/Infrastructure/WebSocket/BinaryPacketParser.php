<?php

namespace TradingPlatform\Infrastructure\WebSocket;

/**
 * Binary packet parser for Dhan WebSocket feed
 * Handles little-endian binary data as per Dhan API v2 specification
 */
class BinaryPacketParser
{
    // Feed response codes from Dhan API
    private const TICKER_PACKET = 2;
    private const QUOTE_PACKET = 4;
    private const OI_PACKET = 5;
    private const PREV_CLOSE_PACKET = 6;
    private const FULL_PACKET = 8;
    private const DISCONNECT_PACKET = 50;

    /**
     * Parse binary packet from Dhan WebSocket
     */
    public function parse(string $binaryData): ?array
    {
        if (strlen($binaryData) < 8) {
            return null; // Minimum header size
        }

        // Parse header (8 bytes)
        $header = $this->parseHeader(substr($binaryData, 0, 8));
        
        // Parse payload based on feed type
        $payload = match($header['feed_code']) {
            self::TICKER_PACKET => $this->parseTickerPacket($binaryData),
            self::QUOTE_PACKET => $this->parseQuotePacket($binaryData),
            self::OI_PACKET => $this->parseOIPacket($binaryData),
            self::PREV_CLOSE_PACKET => $this->parsePrevClosePacket($binaryData),
            self::FULL_PACKET => $this->parseFullPacket($binaryData),
            self::DISCONNECT_PACKET => $this->parseDisconnectPacket($binaryData),
            default => null
        };

        if ($payload === null) {
            return null;
        }

        return array_merge($header, $payload);
    }

    /**
     * Parse 8-byte header (little-endian)
     */
    private function parseHeader(string $data): array
    {
        // Byte 1: Feed response code
        $feedCode = unpack('C', $data[0])[1];
        
        // Bytes 2-3: Message length (int16)
        $messageLength = unpack('v', substr($data, 1, 2))[1];
        
        // Byte 4: Exchange segment
        $exchangeSegment = unpack('C', $data[3])[1];
        
        // Bytes 5-8: Security ID (int32)
        $securityId = unpack('V', substr($data, 4, 4))[1];

        return [
            'feed_code' => $feedCode,
            'message_length' => $messageLength,
            'exchange_segment' => $exchangeSegment,
            'security_id' => $securityId,
        ];
    }

    /**
     * Parse Ticker packet (17 bytes total: 8 header + 9 payload)
     * Bytes 9-12: LTP (float32)
     * Bytes 13-16: LTT (int32 epoch)
     */
    private function parseTickerPacket(string $data): array
    {
        if (strlen($data) < 17) {
            return [];
        }

        // Use little-endian format
        $ltp = unpack('f', substr($data, 8, 4))[1];
        $ltt = unpack('V', substr($data, 12, 4))[1];

        return [
            'ltp' => round($ltp, 2),
            'ltt' => $ltt,
            'timestamp' => date('Y-m-d H:i:s', $ltt),
        ];
    }

    /**
     * Parse Prev Close packet (17 bytes total)
     * Bytes 9-12: Previous close price (float32)
     * Bytes 13-16: Previous OI (int32)
     */
    private function parsePrevClosePacket(string $data): array
    {
        if (strlen($data) < 17) {
            return [];
        }

        $prevClose = unpack('f', substr($data, 8, 4))[1];
        $prevOI = unpack('V', substr($data, 12, 4))[1];

        return [
            'prev_close' => round($prevClose, 2),
            'prev_oi' => $prevOI,
        ];
    }

    /**
     * Parse Quote packet (51 bytes total: 8 header + 43 payload)
     */
    private function parseQuotePacket(string $data): array
    {
        if (strlen($data) < 51) {
            return [];
        }

        $offset = 8; // Start after header

        return [
            'ltp' => round(unpack('f', substr($data, $offset, 4))[1], 2),
            'ltq' => unpack('v', substr($data, $offset + 4, 2))[1],
            'ltt' => unpack('V', substr($data, $offset + 6, 4))[1],
            'atp' => round(unpack('f', substr($data, $offset + 10, 4))[1], 2),
            'volume' => unpack('V', substr($data, $offset + 14, 4))[1],
            'total_sell_qty' => unpack('V', substr($data, $offset + 18, 4))[1],
            'total_buy_qty' => unpack('V', substr($data, $offset + 22, 4))[1],
            'open' => round(unpack('f', substr($data, $offset + 26, 4))[1], 2),
            'close' => round(unpack('f', substr($data, $offset + 30, 4))[1], 2),
            'high' => round(unpack('f', substr($data, $offset + 34, 4))[1], 2),
            'low' => round(unpack('f', substr($data, $offset + 38, 4))[1], 2),
        ];
    }

    /**
     * Parse OI packet (13 bytes total)
     * Bytes 9-12: Open Interest (int32)
     */
    private function parseOIPacket(string $data): array
    {
        if (strlen($data) < 13) {
            return [];
        }

        return [
            'oi' => unpack('V', substr($data, 8, 4))[1],
        ];
    }

    /**
     * Parse Full packet (163 bytes total: 8 header + 155 payload)
     * Includes quote data + market depth (5 levels)
     */
    private function parseFullPacket(string $data): array
    {
        if (strlen($data) < 163) {
            return [];
        }

        $offset = 8;

        $result = [
            'ltp' => round(unpack('f', substr($data, $offset, 4))[1], 2),
            'ltq' => unpack('v', substr($data, $offset + 4, 2))[1],
            'ltt' => unpack('V', substr($data, $offset + 6, 4))[1],
            'atp' => round(unpack('f', substr($data, $offset + 10, 4))[1], 2),
            'volume' => unpack('V', substr($data, $offset + 14, 4))[1],
            'total_sell_qty' => unpack('V', substr($data, $offset + 18, 4))[1],
            'total_buy_qty' => unpack('V', substr($data, $offset + 22, 4))[1],
            'oi' => unpack('V', substr($data, $offset + 26, 4))[1],
            'oi_high' => unpack('V', substr($data, $offset + 30, 4))[1],
            'oi_low' => unpack('V', substr($data, $offset + 34, 4))[1],
            'open' => round(unpack('f', substr($data, $offset + 38, 4))[1], 2),
            'close' => round(unpack('f', substr($data, $offset + 42, 4))[1], 2),
            'high' => round(unpack('f', substr($data, $offset + 46, 4))[1], 2),
            'low' => round(unpack('f', substr($data, $offset + 50, 4))[1], 2),
        ];

        // Parse market depth (5 levels, 20 bytes each = 100 bytes)
        $depthOffset = $offset + 54;
        $result['market_depth'] = [];

        for ($i = 0; $i < 5; $i++) {
            $levelOffset = $depthOffset + ($i * 20);
            
            $result['market_depth'][] = [
                'bid_qty' => unpack('V', substr($data, $levelOffset, 4))[1],
                'ask_qty' => unpack('V', substr($data, $levelOffset + 4, 4))[1],
                'bid_orders' => unpack('v', substr($data, $levelOffset + 8, 2))[1],
                'ask_orders' => unpack('v', substr($data, $levelOffset + 10, 2))[1],
                'bid_price' => round(unpack('f', substr($data, $levelOffset + 12, 4))[1], 2),
                'ask_price' => round(unpack('f', substr($data, $levelOffset + 16, 4))[1], 2),
            ];
        }

        return $result;
    }

    /**
     * Parse disconnect packet
     */
    private function parseDisconnectPacket(string $data): array
    {
        if (strlen($data) < 10) {
            return [];
        }

        $disconnectCode = unpack('v', substr($data, 8, 2))[1];

        return [
            'disconnect_code' => $disconnectCode,
            'reason' => $this->getDisconnectReason($disconnectCode),
        ];
    }

    private function getDisconnectReason(int $code): string
    {
        return match($code) {
            805 => 'Maximum WebSocket connections exceeded (5)',
            800 => 'Invalid token or client ID',
            801 => 'Token expired',
            default => "Unknown disconnect reason: $code"
        };
    }

    /**
     * Calculate Greeks for options from market data
     * Simplified Black-Scholes implementation
     */
    public function calculateGreeks(array $optionData): array
    {
        // This requires: spot price, strike, volatility, time to expiry, risk-free rate
        // Simplified placeholder - full implementation would use Black-Scholes
        
        if (!isset($optionData['spot'], $optionData['strike'], $optionData['volatility'])) {
            return [];
        }

        // Greeks calculation would go here
        // For now, return placeholder
        return [
            'delta' => 0.0,
            'gamma' => 0.0,
            'theta' => 0.0,
            'vega' => 0.0,
            'rho' => 0.0,
        ];
    }
}
