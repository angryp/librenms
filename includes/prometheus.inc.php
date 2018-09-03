<?php

/*
 * LibreNMS
 *
 * Copyright (c) 2014 Neil Lathwood <https://github.com/laf/ http://www.lathwood.co.uk/fa>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

function prometheus_push($device, $measurement, $tags, $fields)
{
    global $prometheus, $config;
    if ($config['prometheus']['enable'] === true) {
        if ($prometheus !== false) {
            try {
                $ch = curl_init();

                set_curl_proxy($ch);
                $vals = "";
                $promtags = "/measurement/".$measurement;

                foreach ($fields as $k => $v) {
                    if ($v !== null) {
                        $k = preg_replace('/([a-z]{1,})/', '$1_', $k); // Generally, metric naming in Prometheus is done with underscores without capital letters
                        $k = substr($k, 0, -1);
                        $k = preg_replace('/([_]{2})/', '_', $k);
                        $k = strtolower($k);
                        $vals = $vals . "$k $v\n";
                    }
                }

                foreach ($tags as $t => $v) {
                    if ($v !== null) {
                        if (strpos($v, "/") === false) {
                            ;
                        } else {
                            $v = str_replace("/", "", $v); // Prometheus does not accept "/" symbol in label names, so we need to strip them out
                        }
                        $promtags = $promtags . "/$t/$v";
                    }
                }

                $promurl = $config['prometheus']['url'].'/metrics/job/'.$config['prometheus']['job'].'/instance/'.$device['hostname'].$promtags;
                $promurl = str_replace(" ", "-", $promurl); // Prometheus doesn't handle tags with spaces in url

                d_echo("\nPrometheus data:\n");
                d_echo($measurement);
                d_echo($tags);
                d_echo($fields);
                d_echo($vals);
                d_echo($promurl);
                d_echo("\nEND\n");

                curl_setopt($ch, CURLOPT_URL, $promurl);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $vals);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                $headers = array();
                $headers[] = "Content-Type: test/plain";
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                curl_exec($ch); // It would be helpful to provide type of metric along with the values itself, but documentation says untyped metrics are still fine for custom exporters

                if (curl_errno($ch)) {
                    d_echo('Error:' . curl_error($ch));
                }
            } catch (Exception $e) {
                d_echo("Caught exception: " . $e->getMessage() . PHP_EOL);
                d_echo($e->getTrace());
            }
        } else {
            c_echo("[%gPrometheus Push Disabled%n]\n");
        }//end if
    }//end if
}// end prometheus_push
