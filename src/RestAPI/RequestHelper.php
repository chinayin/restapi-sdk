<?php

namespace RestAPI;

class RequestHelper
{

    public static function parse_x_request_id($data)
    {
        return isset($data['x-request-id']) ? $data['x-request-id'] : '';
    }

    public static function parse_request_id($data)
    {
        return isset($data['request_id']) ? $data['request_id'] : '';
    }

    public static function parse_curlinfo($r)
    {
        return [
            'http_code' => $r['http_code'] ?? '',
            'total_time' => number_format($r['total_time'] ?? 0, 3),
            'primary_ip' => $r['primary_ip'] ?? '',
            'size_download' => $r['size_download'] ?? '',
            'namelookup_time' => number_format($r['namelookup_time'] ?? 0, 3),
            'connect_time' => number_format($r['connect_time'] ?? 0, 3),
            'pretransfer_time' => number_format($r['pretransfer_time'] ?? 0, 3),
            'starttransfer_time' => number_format($r['starttransfer_time'] ?? 0, 3),
        ];
    }

    public static function parse_response($response, $unionId)
    {
        if (($pos = strpos($response, "\r\n\r\n")) === false) {
            // Crap!
//            self::log($unionId, 'exception', "Missing header/body separator");
            throw new \RuntimeException('Missing header/body separator', -1);
        }
        $headers = substr($response, 0, $pos);
        $body = substr($response, $pos + strlen("\n\r\n\r"));
        // Pretend CRLF = LF for compatibility (RFC 2616, section 19.3)
        $headers = str_replace("\r\n", "\n", $headers);
        // Unfold headers (replace [CRLF] 1*( SP | HT ) with SP) as per RFC 2616 (section 2.2)
        $headers = preg_replace('/\n[ \t]/', ' ', $headers);
        $headers = explode("\n", $headers);
        preg_match('#^HTTP/(1\.\d)[ \t]+(\d+)#i', array_shift($headers), $matches);
        if (empty($matches)) {
//            self::log($unionId, 'exception', "Response could not be parsed");
            throw new \RuntimeException('Response could not be parsed', -1);
        }
        $header2 = [];
        foreach ($headers as $header) {
            [$key, $value] = explode(':', $header, 2);
            $value = trim($value);
            preg_replace('#(\s+)#i', ' ', $value);
            $header2[$key] = $value;
        }
        if (isset($header2['transfer-encoding'])) {
            $body = self::decode_chunked($body);
            unset($header2['transfer-encoding']);
        }
        if (isset($header2['content-encoding'])) {
            $body = self::decompress($body);
        }
        //fsockopen and cURL compatibility
        if (isset($header2['connection'])) {
            unset($header2['connection']);
        }
        return [$header2, $body];
    }

    /**
     * Decoded a chunked body as per RFC 2616
     *
     * @see https://tools.ietf.org/html/rfc2616#section-3.6.1
     *
     * @param string $data Chunked body
     *
     * @return string Decoded body
     */
    protected static function decode_chunked($data)
    {
        if (!preg_match('/^([0-9a-f]+)(?:;(?:[\w-]*)(?:=(?:(?:[\w-]*)*|"(?:[^\r\n])*"))?)*\r\n/i', trim($data))) {
            return $data;
        }

        $decoded = '';
        $encoded = $data;

        while (true) {
            $is_chunked = (bool)preg_match('/^([0-9a-f]+)(?:;(?:[\w-]*)(?:=(?:(?:[\w-]*)*|"(?:[^\r\n])*"))?)*\r\n/i',
                $encoded, $matches);
            if (!$is_chunked) {
                // Looks like it's not chunked after all
                return $data;
            }

            $length = hexdec(trim($matches[1]));
            if ($length === 0) {
                // Ignore trailer headers
                return $decoded;
            }

            $chunk_length = strlen($matches[0]);
            $decoded .= substr($encoded, $chunk_length, $length);
            $encoded = substr($encoded, $chunk_length + $length + 2);

            if (trim($encoded) === '0' || empty($encoded)) {
                return $decoded;
            }
        }
    }

    /**
     * Decompress an encoded body
     * Implements gzip, compress and deflate. Guesses which it is by attempting
     * to decode.
     *
     * @param string $data Compressed data in one of the above formats
     *
     * @return string Decompressed string
     */
    public static function decompress($data)
    {
        if (substr($data, 0, 2) !== "\x1f\x8b" && substr($data, 0, 2) !== "\x78\x9c") {
            // Not actually compressed. Probably cURL ruining this for us.
            return $data;
        }

        if (function_exists('gzdecode') && ($decoded = @gzdecode($data)) !== false) {
            return $decoded;
        } elseif (function_exists('gzinflate') && ($decoded = @gzinflate($data)) !== false) {
            return $decoded;
        } elseif (($decoded = self::compatible_gzinflate($data)) !== false) {
            return $decoded;
        } elseif (function_exists('gzuncompress') && ($decoded = @gzuncompress($data)) !== false) {
            return $decoded;
        }

        return $data;
    }

    /**
     * Decompression of deflated string while staying compatible with the majority of servers.
     * Certain Servers will return deflated data with headers which PHP's gzinflate()
     * function cannot handle out of the box. The following function has been created from
     * various snippets on the gzinflate() PHP documentation.
     * Warning: Magic numbers within. Due to the potential different formats that the compressed
     * data may be returned in, some "magic offsets" are needed to ensure proper decompression
     * takes place. For a simple progmatic way to determine the magic offset in use, see:
     * https://core.trac.wordpress.org/ticket/18273
     *
     * @param string $gzData String to decompress.
     *
     * @return string|bool False on failure.
     * @link  https://secure.php.net/manual/en/function.gzinflate.php#70875
     * @link  https://secure.php.net/manual/en/function.gzinflate.php#77336
     * @since 2.8.1
     * @link  https://core.trac.wordpress.org/ticket/18273
     */
    public static function compatible_gzinflate($gzData)
    {
        // Compressed data might contain a full zlib header, if so strip it for
        // gzinflate()
        if (substr($gzData, 0, 3) == "\x1f\x8b\x08") {
            $i = 10;
            $flg = ord(substr($gzData, 3, 1));
            if ($flg > 0) {
                if ($flg & 4) {
                    [$xlen] = unpack('v', substr($gzData, $i, 2));
                    $i = $i + 2 + $xlen;
                }
                if ($flg & 8) {
                    $i = strpos($gzData, "\0", $i) + 1;
                }
                if ($flg & 16) {
                    $i = strpos($gzData, "\0", $i) + 1;
                }
                if ($flg & 2) {
                    $i = $i + 2;
                }
            }
            $decompressed = self::compatible_gzinflate(substr($gzData, $i));
            if (false !== $decompressed) {
                return $decompressed;
            }
        }

        // If the data is Huffman Encoded, we must first strip the leading 2
        // byte Huffman marker for gzinflate()
        // The response is Huffman coded by many compressors such as
        // java.util.zip.Deflater, Ruby’s Zlib::Deflate, and .NET's
        // System.IO.Compression.DeflateStream.
        //
        // See https://decompres.blogspot.com/ for a quick explanation of this
        // data type
        $huffman_encoded = false;

        // low nibble of first byte should be 0x08
        [, $first_nibble] = unpack('h', $gzData);

        // First 2 bytes should be divisible by 0x1F
        [, $first_two_bytes] = unpack('n', $gzData);

        if (0x08 == $first_nibble && 0 == ($first_two_bytes % 0x1F)) {
            $huffman_encoded = true;
        }

        if ($huffman_encoded) {
            if (false !== ($decompressed = @gzinflate(substr($gzData, 2)))) {
                return $decompressed;
            }
        }

        if ("\x50\x4b\x03\x04" == substr($gzData, 0, 4)) {
            // ZIP file format header
            // Offset 6: 2 bytes, General-purpose field
            // Offset 26: 2 bytes, filename length
            // Offset 28: 2 bytes, optional field length
            // Offset 30: Filename field, followed by optional field, followed
            // immediately by data
            [, $general_purpose_flag] = unpack('v', substr($gzData, 6, 2));

            // If the file has been compressed on the fly, 0x08 bit is set of
            // the general purpose field. We can use this to differentiate
            // between a compressed document, and a ZIP file
            $zip_compressed_on_the_fly = (0x08 == (0x08 & $general_purpose_flag));

            if (!$zip_compressed_on_the_fly) {
                // Don't attempt to decode a compressed zip file
                return $gzData;
            }

            // Determine the first byte of data, based on the above ZIP header
            // offsets:
            $first_file_start = array_sum(unpack('v2', substr($gzData, 26, 4)));
            if (false !== ($decompressed = @gzinflate(substr($gzData, 30 + $first_file_start)))) {
                return $decompressed;
            }
            return false;
        }

        // Finally fall back to straight gzinflate
        if (false !== ($decompressed = @gzinflate($gzData))) {
            return $decompressed;
        }

        // Fallback for all above failing, not expected, but included for
        // debugging and preventing regressions and to track stats
        if (false !== ($decompressed = @gzinflate(substr($gzData, 2)))) {
            return $decompressed;
        }

        return false;
    }

}
