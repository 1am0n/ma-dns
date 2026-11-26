<?php

namespace App;

class Packet {
    public function read($packet) {
        // read header
        $header = unpack('ntransactionId/nflags/nquestions/nanswers/nauthority/nadditional', substr($packet, 0, 12));

        // read domain name
        $offset = 12;
        $domain = $this->readName($packet, $offset);

        // read type and class
        $question = unpack('ntype/nclass', substr($packet, $offset, 4));
        $offset += 4;

        // read all other data
        $sections = [];
        foreach (['answers', 'authority', 'additional'] as $section) {
            $sections[$section] = [];

            for ($i = 0; $i < $header[$section]; $i++) {
                $name = $this->readName($packet, $offset);

                if ($offset + 10 > strlen($packet)) {
                    throw new \RuntimeException("Incomplete record header");
                }

                $record = unpack('ntype/nclass/Nttl/nlength', substr($packet, $offset, 10));
                $offset += 10;

                $dataEnd = $offset + $record['length'];

                if ($dataEnd > strlen($packet)) {
                    throw new \RuntimeException("Incomplete record data");
                }

                $data = substr($packet, $offset, $record['length']);

                if ($record['type'] === 1 && $record['length'] === 4) {
                    $data = inet_ntop($data); // A
                } elseif ($record['type'] === 2) {
                    $data = $this->readName($packet, $offset); // NS

                    if ($offset !== $dataEnd) {
                        throw new \RuntimeException("Ivalid NS record length");
                    }
                }

                $offset = $dataEnd;

                $record['name'] = $name;
                $record['data'] = $data;

                $sections[$section][] = $record;
            }
        }

        return [
            'header' => $header,
            'domain' => $domain,
            'question' => $question,
            'answers' => $sections['answers'],
            'authority' => $sections['authority'],
            'additional' => $sections['additional']
        ];
    }

    public function readName(string $packet, int &$offset):string {
        $labels = [];

        while ($offset < strlen($packet)) {
            $length = ord($packet[$offset++]);

            if ($length === 0) {
                return implode('.', $labels);
            }

            if ($length > 63 || $offset + $length > strlen($packet)) {
                throw new \RuntimeException("Invalid or unsupported label");
            }

            $labels[] = substr($packet, $offset, $length);
            $offset += $length;
        }

        throw new \RuntimeException("Incomplete domain");
    }

    public function encodeName(string $name): string {
        $name = rtrim($name, '.');
        $encoded = '';

        if ($name !== '') {
            foreach (explode('.', $name) as $label) {
                $encoded .= chr(strlen($label)) . $label;
            }
        }

        return $encoded . "\0";
    }
}