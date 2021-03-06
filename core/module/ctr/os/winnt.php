<?php

/**
 * winnt Platform Module
 *
 * Author Jerry Shaw <jerry-shaw@live.com>
 * Author 秋水之冰 <27206617@qq.com>
 *
 * Copyright 2017 Jerry Shaw
 * Copyright 2017 秋水之冰
 *
 * This file is part of NervSys.
 *
 * NervSys is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NervSys is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NervSys. If not, see <http://www.gnu.org/licenses/>.
 */

namespace core\ctr\os;

use \core\lib\os as os;

class winnt implements os
{
    /**
     * Get Machine hash code
     *
     * @return string
     */
    public static function get_hash(): string
    {
        $queries = [
            'wmic nic get AdapterType, MACAddress, Manufacturer, Name, PNPDeviceID /format:value',
            'wmic cpu get Caption, CreationClassName, Family, Manufacturer, Name, ProcessorId, ProcessorType, Revision /format:value',
            'wmic bios get Manufacturer, Name, SerialNumber, Version /format:value',
            'wmic baseboard get Manufacturer, Product, SerialNumber, Version /format:value',
            'wmic diskdrive get Model, Size /format:value',
            'wmic memorychip get BankLabel, Capacity /format:value'
        ];

        foreach ($queries as $query) exec($query, $output);

        unset($queries, $query);
        return hash('sha256', implode('|', array_filter($output)));
    }

    /**
     * Get PHP executable info
     *
     * @return array
     */
    public static function exec_info(): array
    {
        exec('wmic process where ProcessId="' . getmypid() . '" get ProcessId, CommandLine, ExecutablePath /format:value', $output, $status);

        //No authority
        if (0 !== $status) {
            if (DEBUG) {
                fwrite(STDOUT, 'Access denied! Please check your authority!' . PHP_EOL);
                fclose(STDOUT);
            }
            exit;
        }

        unset($status);

        //Process output data
        if (!empty($output)) {

            $key = 0;
            $process = [];

            foreach ($output as $line) {
                if ('' === $line) {
                    ++$key;
                    continue;
                }

                if (false === strpos($line, '=')) continue;

                list($name, $value) = explode('=', $line);
                $process[$key][$name] = $value;
            }

            unset($output, $key, $line, $name, $value);

            if (!empty($process)) {
                foreach ($process as $info) {
                    if (false !== strpos($info['CommandLine'], 'api.php')) {
                        $result = ['pid' => &$info['ProcessId'], 'cmd' => &$info['CommandLine'], 'path' => &$info['ExecutablePath']];
                        unset($process, $info);
                        return $result;
                    }
                }
            }
        }
        return ['pid' => 0, 'cmd' => '', 'path' => ''];
    }
}